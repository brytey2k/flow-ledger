<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\Tenant\CreatePaymentRequestDto;
use App\DTOs\Tenant\DisbursePaymentRequestDto;
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
                'status' => 'draft',
            ]);

            foreach ($dto->items as $item) {
                $request->items()->create([
                    'description' => $item->description,
                    'amount' => $item->amount,
                    'account_code_id' => $item->accountCodeId,
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
            $template = WorkflowTemplate::resolveForBranch($request->type, $request->branch_id);

            $request->update([
                'status' => 'in_workflow',
                'submitted_at' => now(),
            ]);

            activity()
                ->performedOn($request)
                ->causedBy($user)
                ->event('request.submitted')
                ->withProperties(['old_status' => 'draft', 'new_status' => 'in_workflow'])
                ->log('Submitted for approval');

            $this->engine->startWorkflow($request, $template, $user);
        });
    }

    public function disburse(PaymentRequest $request, DisbursePaymentRequestDto $dto, User|null $user = null): void
    {
        DB::transaction(function () use ($request, $dto, $user): void {
            $request->update([
                'status' => 'disbursed',
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
                ->withProperties(['old_status' => 'approved', 'new_status' => 'disbursed', 'method' => $dto->method->value])
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
                    'account_code_id' => $item->accountCodeId,
                    'receipt_number' => $item->receiptNumber,
                ]);
            }

            activity()
                ->performedOn($paymentRequest)
                ->causedBy($user)
                ->event('request.updated')
                ->withProperties(['new_status' => 'sent_back'])
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
                $activeInstance->instanceStages()
                    ->whereIn('status', ['pending', 'active'])
                    ->update(['status' => 'cancelled', 'completed_at' => now()]);

                $activeInstance->update(['status' => 'cancelled']);
            }

            $request->update(['status' => 'cancelled']);

            activity()
                ->performedOn($request)
                ->causedBy($user)
                ->event('request.cancelled')
                ->withProperties(['old_status' => $oldStatus, 'new_status' => 'cancelled'])
                ->log('Request cancelled');
        });
    }
}
