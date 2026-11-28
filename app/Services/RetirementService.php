<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\Tenant\CreateRetirementRequestDto;
use App\DTOs\Tenant\RetirementRequestItemDto;
use App\Enums\Tenant\PaymentRequestStatus;
use App\Exceptions\PaymentRequestNotFoundException;
use App\Models\Tenant\PaymentRequest;
use App\Models\Tenant\RetirementRequest;
use App\Models\Tenant\User;
use App\Models\Tenant\WorkflowTemplate;
use App\Support\Money;
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
            // Prevent race-created duplicate active retirements in-app before attempting insert.
            if (RetirementRequest::where('payment_request_id', $paymentRequest->id)->whereNotIn('status', ['cancelled', 'settled'])->exists()) {
                // Use ValidationException so controllers return a 422 with errors
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'items' => ['This advance has already been retired.'],
                ]);
            }

            $figures = $this->settlementFigures($dto->items, $paymentRequest->getAttribute('total_amount'));

            $retirement = RetirementRequest::create([
                'payment_request_id' => $paymentRequest->id,
                'status' => 'draft',
                ...$figures,
                'notes' => $dto->notes,
                'no_money_spent' => $dto->didNotSpendMoney,
            ]);

            foreach ($dto->items as $item) {
                $retirement->items()->create([
                    'description' => $item->description,
                    'amount' => $item->amount,
                    'cost_code_id' => $item->costCodeId,
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
            // Lock the row and check again: the controller's status check ran before this transaction, so two
            // simultaneous calls could otherwise both settle and post to the cashbook twice.
            RetirementRequest::lockForUpdate()->findOrFail($retirement->id);
            $retirement->refresh();

            if ($retirement->status !== 'approved') {
                throw new \Symfony\Component\HttpKernel\Exception\ConflictHttpException('This retirement is no longer awaiting settlement.');
            }

            $retirement->update([
                'status' => 'settled',
                'settled_at' => now(),
                'settled_by_user_id' => $user?->id,
                'settlement_notes' => $notes,
            ]);

            $retirement->loadMissing('paymentRequest');
            $paymentRequest = $retirement->paymentRequest;

            if (! $paymentRequest instanceof PaymentRequest) {
                throw new PaymentRequestNotFoundException('Cannot settle retirement: associated payment request not found.');
            }

            $paymentRequest->update(['status' => PaymentRequestStatus::Retired->value]);

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
            $figures = $this->settlementFigures($dto->items, $retirement->paymentRequest?->getAttribute('total_amount'));

            $retirement->update([
                'notes' => $dto->notes,
                ...$figures,
                'no_money_spent' => $dto->didNotSpendMoney,
            ]);

            $retirement->items()->delete();

            foreach ($dto->items as $item) {
                $retirement->items()->create([
                    'description' => $item->description,
                    'amount' => $item->amount,
                    'cost_code_id' => $item->costCodeId,
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
            $figures = $this->settlementFigures($dto->items, $retirement->paymentRequest?->getAttribute('total_amount'));

            $retirement->update([
                'notes' => $dto->notes,
                ...$figures,
                'no_money_spent' => $dto->didNotSpendMoney,
            ]);

            $retirement->items()->delete();

            foreach ($dto->items as $item) {
                $retirement->items()->create([
                    'description' => $item->description,
                    'amount' => $item->amount,
                    'cost_code_id' => $item->costCodeId,
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
            $retirement = RetirementRequest::lockForUpdate()->findOrFail($retirement->id);
            $oldStatus = $retirement->status;
            $activeInstance = $retirement->activeWorkflowInstance;

            if ($activeInstance instanceof \App\Models\Tenant\WorkflowInstance) {
                $activeInstance = \App\Models\Tenant\WorkflowInstance::lockForUpdate()->findOrFail($activeInstance->id);
                if ($activeInstance->hasDocumentHold()) {
                    throw new \Symfony\Component\HttpKernel\Exception\ConflictHttpException(
                        'Cancel the document window before cancelling this request.',
                    );
                }
                $activeStage = $activeInstance->instanceStages()
                    ->whereIn('status', ['active', 'blocked'])
                    ->first();

                $activeInstance->instanceStages()
                    ->whereIn('status', ['pending', 'active', 'blocked'])
                    ->update(['status' => 'cancelled', 'completed_at' => now()]);

                $activeInstance->update([
                    'status' => 'cancelled',
                    'cancelled_at_stage_id' => $activeStage?->id,
                ]);
            }

            $retirement->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'cancelled_by_user_id' => $user?->id,
            ]);

            activity()
                ->performedOn($retirement)
                ->causedBy($user)
                ->event('retirement.cancelled')
                ->withProperties(['old_status' => $oldStatus, 'new_status' => 'cancelled'])
                ->log('Retirement cancelled');
        });
    }

    /**
     * What the staff member spent against what they were advanced, and so which way the difference moves
     * when the retirement is settled.
     *
     * @param list<RetirementRequestItemDto> $items
     * @param mixed $advanceAmount
     *
     * @return array{total_amount_expended: numeric-string, difference_amount: numeric-string, difference_type: string}
     */
    private function settlementFigures(array $items, mixed $advanceAmount): array
    {
        $totalExpended = Money::sum(array_map(fn(RetirementRequestItemDto $item): string => $item->amount, $items));
        $difference = bcsub($totalExpended, Money::of($advanceAmount), 2);

        return [
            'total_amount_expended' => $totalExpended,
            'difference_amount' => Money::of(ltrim($difference, '-')),
            'difference_type' => match (bccomp($difference, Money::ZERO, 2)) {
                1 => 'pay_to_staff',
                -1 => 'refund_to_company',
                default => 'nil',
            },
        ];
    }
}
