<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\Tenant\CreateRetirementRequestDto;
use App\Models\Tenant\PaymentRequest;
use App\Models\Tenant\RetirementRequest;
use App\Models\Tenant\User;
use App\Models\Tenant\WorkflowTemplate;
use Illuminate\Support\Facades\DB;

class RetirementService
{
    public function __construct(
        private readonly WorkflowEngineService $engine,
        private readonly CashbookService $cashbook,
    ) {}

    public function createDraft(PaymentRequest $paymentRequest, CreateRetirementRequestDto $dto, User|null $user = null): RetirementRequest
    {
        return DB::transaction(function () use ($paymentRequest, $dto, $user): RetirementRequest {
            $totalExpended = array_sum(array_map(fn($item) => $item->amount, $dto->items));
            $rawAmount = $paymentRequest->getAttribute('total_amount');
            $advanceAmount = is_numeric($rawAmount) ? (float) $rawAmount : 0.0;
            $diff = round($totalExpended - $advanceAmount, 2);

            $differenceType = match (true) {
                $diff > 0 => 'pay_to_staff',
                $diff < 0 => 'refund_to_company',
                default => 'nil',
            };

            $retirement = RetirementRequest::create([
                'payment_request_id' => $paymentRequest->id,
                'status' => 'draft',
                'total_amount_expended' => $totalExpended,
                'difference_amount' => abs($diff),
                'difference_type' => $differenceType,
                'notes' => $dto->notes,
            ]);

            foreach ($dto->items as $item) {
                $retirement->items()->create([
                    'description' => $item->description,
                    'amount' => $item->amount,
                    'account_code_id' => $item->accountCodeId,
                    'receipt_number' => $item->receiptNumber,
                ]);
            }

            activity()
                ->performedOn($retirement)
                ->causedBy($user)
                ->event('retirement.created')
                ->withProperties(['new_status' => 'draft'])
                ->log('Retirement created as draft');

            return $retirement;
        });
    }

    public function submit(RetirementRequest $retirement, User|null $user = null): void
    {
        DB::transaction(function () use ($retirement, $user): void {
            $retirement->loadMissing('paymentRequest');
            $branchId = $retirement->paymentRequest?->branch_id;
            $template = WorkflowTemplate::resolveForBranch('retirement', $branchId);

            $retirement->update([
                'status' => 'in_workflow',
                'submitted_at' => now(),
            ]);

            activity()
                ->performedOn($retirement)
                ->causedBy($user)
                ->event('retirement.submitted')
                ->withProperties(['old_status' => 'draft', 'new_status' => 'in_workflow'])
                ->log('Submitted for approval');

            $this->engine->startWorkflow($retirement, $template, $user);
        });
    }

    public function settle(RetirementRequest $retirement, string|null $notes, User|null $user = null): void
    {
        DB::transaction(function () use ($retirement, $notes, $user): void {
            $retirement->update([
                'status' => 'settled',
                'settled_at' => now(),
                'settled_by_user_id' => $user?->id,
                'settlement_notes' => $notes,
            ]);

            $this->cashbook->recordRetirementSettlement($retirement, $user);

            activity()
                ->performedOn($retirement)
                ->causedBy($user)
                ->event('retirement.settled')
                ->withProperties(['old_status' => 'approved', 'new_status' => 'settled'])
                ->log('Difference settled');
        });
    }

    public function updateDraft(RetirementRequest $retirement, CreateRetirementRequestDto $dto, User|null $user = null): RetirementRequest
    {
        return DB::transaction(function () use ($retirement, $dto, $user): RetirementRequest {
            $totalExpended = array_sum(array_map(fn($item) => $item->amount, $dto->items));
            $rawAmount = $retirement->paymentRequest?->getAttribute('total_amount');
            $advanceAmount = is_numeric($rawAmount) ? (float) $rawAmount : 0.0;
            $diff = round($totalExpended - $advanceAmount, 2);

            $differenceType = match (true) {
                $diff > 0 => 'pay_to_staff',
                $diff < 0 => 'refund_to_company',
                default => 'nil',
            };

            $retirement->update([
                'notes' => $dto->notes,
                'total_amount_expended' => $totalExpended,
                'difference_amount' => abs($diff),
                'difference_type' => $differenceType,
            ]);

            $retirement->items()->delete();

            foreach ($dto->items as $item) {
                $retirement->items()->create([
                    'description' => $item->description,
                    'amount' => $item->amount,
                    'account_code_id' => $item->accountCodeId,
                    'receipt_number' => $item->receiptNumber,
                ]);
            }

            activity()
                ->performedOn($retirement)
                ->causedBy($user)
                ->event('retirement.updated')
                ->withProperties(['new_status' => 'draft'])
                ->log('Retirement draft updated');

            return $retirement->refresh();
        });
    }

    public function updateSentBack(RetirementRequest $retirement, CreateRetirementRequestDto $dto, User|null $user = null): RetirementRequest
    {
        return DB::transaction(function () use ($retirement, $dto, $user): RetirementRequest {
            $totalExpended = array_sum(array_map(fn($item) => $item->amount, $dto->items));
            $rawAmount = $retirement->paymentRequest?->getAttribute('total_amount');
            $advanceAmount = is_numeric($rawAmount) ? (float) $rawAmount : 0.0;
            $diff = round($totalExpended - $advanceAmount, 2);

            $differenceType = match (true) {
                $diff > 0 => 'pay_to_staff',
                $diff < 0 => 'refund_to_company',
                default => 'nil',
            };

            $retirement->update([
                'notes' => $dto->notes,
                'total_amount_expended' => $totalExpended,
                'difference_amount' => abs($diff),
                'difference_type' => $differenceType,
            ]);

            $retirement->items()->delete();

            foreach ($dto->items as $item) {
                $retirement->items()->create([
                    'description' => $item->description,
                    'amount' => $item->amount,
                    'account_code_id' => $item->accountCodeId,
                    'receipt_number' => $item->receiptNumber,
                ]);
            }

            activity()
                ->performedOn($retirement)
                ->causedBy($user)
                ->event('retirement.updated')
                ->withProperties(['new_status' => 'sent_back'])
                ->log('Retirement updated while sent back');

            return $retirement->refresh();
        });
    }

    public function cancel(RetirementRequest $retirement, User|null $user = null): void
    {
        DB::transaction(function () use ($retirement, $user): void {
            $oldStatus = $retirement->status;
            $activeInstance = $retirement->activeWorkflowInstance;

            if ($activeInstance instanceof \App\Models\Tenant\WorkflowInstance) {
                $activeInstance->instanceStages()
                    ->whereIn('status', ['pending', 'active'])
                    ->update(['status' => 'cancelled', 'completed_at' => now()]);

                $activeInstance->update(['status' => 'cancelled']);
            }

            $retirement->update(['status' => 'cancelled']);

            activity()
                ->performedOn($retirement)
                ->causedBy($user)
                ->event('retirement.cancelled')
                ->withProperties(['old_status' => $oldStatus, 'new_status' => 'cancelled'])
                ->log('Retirement cancelled');
        });
    }
}
