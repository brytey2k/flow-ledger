<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\Tenant\CashbookEntryDto;
use App\Events\CashbookBalanceChanged;
use App\Exceptions\InsufficientCashbookBalanceException;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Cashbook;
use App\Models\Tenant\CashbookEntry;
use App\Models\Tenant\PaymentRequest;
use App\Models\Tenant\RetirementRequest;
use App\Models\Tenant\User;
use Illuminate\Support\Facades\DB;

class CashbookService
{
    /**
     * Get the branch's cashbook, creating it in the branch's current currency if it
     * doesn't exist yet (falling back to $fallbackCurrencyId if the branch itself has
     * none configured). Locks the branch row so this can't race with a concurrent
     * branch-currency update — either the cashbook is created first (and the currency
     * update then sees it exists and is rejected), or the currency update commits first
     * (and the cashbook is created in the new currency). No interleaving is possible.
     *
     * @param Branch $branch
     * @param int|null|null $fallbackCurrencyId
     */
    public function getOrCreateForBranch(Branch $branch, int|null $fallbackCurrencyId = null): Cashbook
    {
        return DB::transaction(function () use ($branch, $fallbackCurrencyId): Cashbook {
            $lockedBranch = Branch::whereKey($branch->id)->lockForUpdate()->firstOrFail();

            return Cashbook::firstOrCreate(
                ['branch_id' => $lockedBranch->id],
                ['currency_id' => $lockedBranch->currency_id ?? $fallbackCurrencyId, 'balance' => 0],
            );
        });
    }

    public function recordDisbursement(PaymentRequest $request, User|null $user = null): void
    {
        $raw = $request->getAttribute('total_amount');
        $amount = is_numeric($raw) ? (float) $raw : 0.0;

        $branch = $request->branch;
        assert($branch instanceof Branch);
        $cashbook = $this->getOrCreateForBranch($branch, $request->currency_id);

        // Prevent disbursement if balance is already negative, or positive but insufficient.
        if ($cashbook->balance < 0 || ($cashbook->balance > 0 && $cashbook->balance < $amount)) {
            throw new InsufficientCashbookBalanceException('Insufficient cashbook balance for disbursement.');
        }

        CashbookEntry::create([
            'cashbook_id' => $cashbook->id,
            'type' => 'credit',
            'amount' => $amount,
            'description' => 'Payment disbursed',
            'entry_date' => today(),
            'sourceable_type' => PaymentRequest::class,
            'sourceable_id' => $request->id,
            'created_by_user_id' => $user?->id,
        ]);

        /** @var float $previousBalance */
        $previousBalance = $cashbook->getAttribute('balance');
        $cashbook->decrement('balance', $amount);
        /** @var float $newBalance */
        $newBalance = $cashbook->getAttribute('balance');

        activity()
            ->performedOn($cashbook)
            ->causedBy($user)
            ->event('cashbook.credit')
            ->withProperties(['amount' => $amount, 'source' => 'payment_request', 'source_id' => $request->id])
            ->log('Cash debited for disbursement');

        CashbookBalanceChanged::dispatch($cashbook, $previousBalance, $newBalance);
    }

    public function recordRetirementSettlement(RetirementRequest $retirement, User|null $user = null): void
    {
        if ($retirement->difference_type === 'nil') {
            return;
        }

        $paymentRequest = $retirement->paymentRequest()->firstOrFail();
        $branch = $paymentRequest->branch;
        assert($branch instanceof Branch);
        $cashbook = $this->getOrCreateForBranch($branch, $paymentRequest->currency_id);

        $raw = $retirement->getAttribute('difference_amount');
        $amount = is_numeric($raw) ? (float) $raw : 0.0;
        $isCredit = $retirement->difference_type === 'pay_to_staff';

        // Check if balance is positive but insufficient for credit transactions (paying staff).
        if ($isCredit && $cashbook->balance > 0 && $cashbook->balance < $amount) {
            throw new InsufficientCashbookBalanceException('Insufficient cashbook balance for retirement settlement.');
        }

        $type = $isCredit ? 'credit' : 'debit';
        $description = $isCredit
            ? 'Retirement settlement — additional payment to staff'
            : 'Retirement settlement — refund received from staff';
        $event = $isCredit ? 'cashbook.credit' : 'cashbook.debit';

        CashbookEntry::create([
            'cashbook_id' => $cashbook->id,
            'type' => $type,
            'amount' => $amount,
            'description' => $description,
            'entry_date' => today(),
            'sourceable_type' => RetirementRequest::class,
            'sourceable_id' => $retirement->id,
            'created_by_user_id' => $user?->id,
        ]);

        /** @var float $previousBalance */
        $previousBalance = $cashbook->getAttribute('balance');

        if ($isCredit) {
            $cashbook->decrement('balance', $amount);
        } else {
            $cashbook->increment('balance', $amount);
        }

        /** @var float $newBalance */
        $newBalance = $cashbook->getAttribute('balance');

        activity()
            ->performedOn($cashbook)
            ->causedBy($user)
            ->event($event)
            ->withProperties(['amount' => $amount, 'source' => 'retirement_request', 'source_id' => $retirement->id, 'difference_type' => $retirement->difference_type])
            ->log($isCredit ? 'Cash debited for retirement settlement' : 'Cash credited from retirement refund');

        CashbookBalanceChanged::dispatch($cashbook, $previousBalance, $newBalance);
    }

    public function recordManualReceipt(Cashbook $cashbook, CashbookEntryDto $dto, User|null $user = null): CashbookEntry
    {
        $amount = is_numeric($dto->amount) ? (float) $dto->amount : 0.0;

        $entry = CashbookEntry::create([
            'cashbook_id' => $cashbook->id,
            'type' => 'debit',
            'amount' => $amount,
            'description' => 'Manual receipt',
            'entry_date' => $dto->entryDate,
            'reference' => $dto->reference,
            'notes' => $dto->notes,
            'sourceable_type' => null,
            'sourceable_id' => null,
            'created_by_user_id' => $user?->id,
        ]);

        /** @var float $previousBalance */
        $previousBalance = $cashbook->getAttribute('balance');
        $cashbook->increment('balance', $amount);
        /** @var float $newBalance */
        $newBalance = $cashbook->getAttribute('balance');

        activity()
            ->performedOn($cashbook)
            ->causedBy($user)
            ->event('cashbook.receipt')
            ->withProperties(['amount' => $amount])
            ->log('Manual receipt recorded');

        CashbookBalanceChanged::dispatch($cashbook, $previousBalance, $newBalance);

        return $entry;
    }

    public function deleteManualReceipt(CashbookEntry $entry): void
    {
        if ($entry->isAutoGenerated()) {
            throw new \LogicException('Auto-generated cashbook entries cannot be deleted.');
        }

        $raw = $entry->getAttribute('amount');
        $amount = is_numeric($raw) ? (float) $raw : 0.0;
        $cashbook = $entry->cashbook;

        /** @var float $previousBalance */
        $previousBalance = $cashbook->getAttribute('balance');
        $cashbook->decrement('balance', $amount);
        /** @var float $newBalance */
        $newBalance = $cashbook->getAttribute('balance');
        $entry->delete();

        activity()
            ->performedOn($cashbook)
            ->event('cashbook.receipt_deleted')
            ->withProperties(['amount' => $amount])
            ->log('Manual receipt deleted');

        CashbookBalanceChanged::dispatch($cashbook, $previousBalance, $newBalance);
    }
}
