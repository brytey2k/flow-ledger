<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\Tenant\CreatePaymentRequestDto;
use App\DTOs\Tenant\DisbursePaymentRequestDto;
use App\Enums\Tenant\PaymentRequestStatus;
use App\Enums\Tenant\PaymentRequestType;
use App\Models\Tenant\PaymentRequest;
use App\Models\Tenant\User;
use App\Models\Tenant\WorkflowTemplate;
use Illuminate\Support\Facades\DB;

class PaymentRequestService
{
    public function __construct(
        private readonly WorkflowEngineService $engine,
        private readonly NotificationService $notifications,
        private readonly CashbookService $cashbook,
    ) {}

    public function createDraft(CreatePaymentRequestDto $dto, User|null $user = null): PaymentRequest
    {
        return DB::transaction(function () use ($dto, $user): PaymentRequest {
            $totalAmount = array_sum(array_column(
                array_map(fn($item) => ['amount' => $item->amount], $dto->items),
                'amount',
            ));

            $request = PaymentRequest::create([
                'staff_id' => $dto->staffId,
                'branch_id' => $dto->branchId,
                'currency_id' => $dto->currencyId,
                'type' => $dto->type,
                'notes' => $dto->notes,
                'total_amount' => $totalAmount,
                'status' => PaymentRequestStatus::Draft->value,
            ]);

            foreach ($dto->items as $item) {
                $request->items()->create([
                    'description' => $item->description,
                    'amount' => $item->amount,
                    'cost_code_id' => $item->costCodeId,
                    'receipt_number' => $item->receiptNumber,
                ]);
            }

            activity()
                ->performedOn($request)
                ->causedBy($user)
                ->event('request.created')
                ->withProperties(['new_status' => 'draft'])
                ->log('Request created as draft');

            return $request;
        });
    }

    public function submit(PaymentRequest $request, User|null $user = null): void
    {
        DB::transaction(function () use ($request, $user): void {
            // Expense-type requests skip the workflow and are marked ready for retirement
            if ($request->type === PaymentRequestType::Expense->value) {
                $request->update([
                    'status' => PaymentRequestStatus::ReadyForRetirement->value,
                    'submitted_at' => now(),
                ]);

                activity()
                    ->performedOn($request)
                    ->causedBy($user)
                    ->event('request.submitted')
                    ->withProperties(['old_status' => PaymentRequestStatus::Draft->value, 'new_status' => PaymentRequestStatus::ReadyForRetirement->value])
                    ->log('Submitted as ready for retirement');

                return;
            }

            $template = WorkflowTemplate::resolveForBranch($request->type, $request->branch_id);

            $request->update([
                'status' => PaymentRequestStatus::InWorkflow->value,
                'submitted_at' => now(),
            ]);

            activity()
                ->performedOn($request)
                ->causedBy($user)
                ->event('request.submitted')
                ->withProperties(['old_status' => PaymentRequestStatus::Draft->value, 'new_status' => PaymentRequestStatus::InWorkflow->value])
                ->log('Submitted for approval');

            $this->engine->startWorkflow($request, $template, $user);
        });
    }

    public function disburse(PaymentRequest $request, DisbursePaymentRequestDto $dto, User|null $user = null): void
    {
        DB::transaction(function () use ($request, $dto, $user): void {
            $request->update([
                'status' => PaymentRequestStatus::Disbursed->value,
                'disbursed_at' => now(),
                'disbursed_by_user_id' => $user?->id,
                'disbursement_method' => $dto->method,
                'disbursement_reference' => $dto->reference,
            ]);

            $this->cashbook->recordDisbursement($request, $user);

            activity()
                ->performedOn($request)
                ->causedBy($user)
                ->event('request.disbursed')
                ->withProperties(['old_status' => PaymentRequestStatus::Approved->value, 'new_status' => PaymentRequestStatus::Disbursed->value, 'method' => $dto->method->value])
                ->log('Disbursed');
        });

        $this->notifications->notifyDisbursed($request);
    }

    public function updateSentBack(PaymentRequest $paymentRequest, CreatePaymentRequestDto $dto, User|null $user = null): PaymentRequest
    {
        return DB::transaction(function () use ($paymentRequest, $dto, $user): PaymentRequest {
            $totalAmount = array_sum(array_column(
                array_map(fn($item) => ['amount' => $item->amount], $dto->items),
                'amount',
            ));

            $paymentRequest->update([
                'currency_id' => $dto->currencyId,
                'notes' => $dto->notes,
                'total_amount' => $totalAmount,
            ]);

            $paymentRequest->items()->delete();

            foreach ($dto->items as $item) {
                $paymentRequest->items()->create([
                    'description' => $item->description,
                    'amount' => $item->amount,
                    'cost_code_id' => $item->costCodeId,
                    'receipt_number' => $item->receiptNumber,
                ]);
            }

            activity()
                ->performedOn($paymentRequest)
                ->causedBy($user)
                ->event('request.updated')
                ->withProperties(['new_status' => PaymentRequestStatus::SentBack->value])
                ->log('Request updated while sent back');

            return $paymentRequest->refresh();
        });
    }

    public function cancel(PaymentRequest $request, User|null $user = null): void
    {
        DB::transaction(function () use ($request, $user): void {
            $oldStatus = $request->status;
            $activeInstance = $request->activeWorkflowInstance;

            if ($activeInstance instanceof \App\Models\Tenant\WorkflowInstance) {
                $activeStage = $activeInstance->activeInstanceStages()->first();

                // Only mark completed_at for stages that were active. Pending stages are cancelled without completed_at.
                $activeInstance->instanceStages()
                    ->where('status', 'active')
                    ->update(['status' => 'cancelled', 'completed_at' => now()]);

                $activeInstance->instanceStages()
                    ->where('status', 'pending')
                    ->update(['status' => 'cancelled']);

                $activeInstance->update([
                    'status' => 'cancelled',
                    'cancelled_at_stage_id' => $activeStage?->id,
                ]);
            }

            $request->update(['status' => PaymentRequestStatus::Cancelled->value]);

            activity()
                ->performedOn($request)
                ->causedBy($user)
                ->event('request.cancelled')
                ->withProperties(['old_status' => $oldStatus, 'new_status' => PaymentRequestStatus::Cancelled->value])
                ->log('Request cancelled');
        });
    }

    public function decline(PaymentRequest $request, User|null $user = null): void
    {
        DB::transaction(function () use ($request, $user): void {
            $oldStatus = $request->status;
            $activeInstance = $request->activeWorkflowInstance;

            if ($activeInstance instanceof \App\Models\Tenant\WorkflowInstance) {
                // Only mark completed_at for stages that were active. Pending stages are cancelled without completed_at.
                $activeInstance->instanceStages()
                    ->where('status', 'active')
                    ->update(['status' => 'cancelled', 'completed_at' => now()]);

                $activeInstance->instanceStages()
                    ->where('status', 'pending')
                    ->update(['status' => 'cancelled']);

                $activeInstance->update(['status' => 'cancelled']);
            }

            $request->update(['status' => PaymentRequestStatus::Denied->value]);

            activity()
                ->performedOn($request)
                ->causedBy($user)
                ->event('request.denied')
                ->withProperties(['old_status' => $oldStatus, 'new_status' => PaymentRequestStatus::Denied->value])
                ->log('Request denied');
        });
    }
}
