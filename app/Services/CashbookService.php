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
use App\Support\Money;
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
        $amount = Money::of($request->getAttribute('total_amount'));

        $branch = $request->branch;
        assert($branch instanceof Branch);

        $cashbook = $this->getOrCreateForBranch($branch, $request->currency_id);

        DB::transaction(function () use ($request, $user, $amount, $cashbook): void {
            $this->lockBalance($cashbook);

            if (! $this->coversPayout($cashbook, $amount)) {
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

            activity()
                ->performedOn($cashbook)
                ->causedBy($user)
                ->event('cashbook.credit')
                ->withProperties(['amount' => $amount, 'source' => 'payment_request', 'source_id' => $request->id])
                ->log('Cash debited for disbursement');

            $this->changeBalance($cashbook, bcsub(Money::ZERO, $amount, 2));
        });
    }

    public function recordRetirementSettlement(RetirementRequest $retirement, User|null $user = null): void
    {
        if ($retirement->difference_type === 'nil') {
            return;
        }

        $paymentRequest = $retirement->paymentRequest()->firstOrFail();
        $branch = $paymentRequest->branch;
        assert($branch instanceof Branch);

        $amount = Money::of($retirement->getAttribute('difference_amount'));
        $isCredit = $retirement->difference_type === 'pay_to_staff';

        $cashbook = $this->getOrCreateForBranch($branch, $paymentRequest->currency_id);

        DB::transaction(function () use ($retirement, $user, $amount, $isCredit, $cashbook): void {
            $this->lockBalance($cashbook);

            if ($isCredit && ! $this->coversPayout($cashbook, $amount)) {
                throw new InsufficientCashbookBalanceException('Insufficient cashbook balance for retirement settlement.');
            }

            CashbookEntry::create([
                'cashbook_id' => $cashbook->id,
                'type' => $isCredit ? 'credit' : 'debit',
                'amount' => $amount,
                'description' => $isCredit
                    ? 'Retirement settlement — additional payment to staff'
                    : 'Retirement settlement — refund received from staff',
                'entry_date' => today(),
                'sourceable_type' => RetirementRequest::class,
                'sourceable_id' => $retirement->id,
                'created_by_user_id' => $user?->id,
            ]);

            activity()
                ->performedOn($cashbook)
                ->causedBy($user)
                ->event($isCredit ? 'cashbook.credit' : 'cashbook.debit')
                ->withProperties(['amount' => $amount, 'source' => 'retirement_request', 'source_id' => $retirement->id, 'difference_type' => $retirement->difference_type])
                ->log($isCredit ? 'Cash debited for retirement settlement' : 'Cash credited from retirement refund');

            $this->changeBalance($cashbook, $isCredit ? bcsub(Money::ZERO, $amount, 2) : $amount);
        });
    }

    public function recordManualReceipt(Cashbook $cashbook, CashbookEntryDto $dto, User|null $user = null): CashbookEntry
    {
        $amount = Money::of($dto->amount);

        return DB::transaction(function () use ($cashbook, $dto, $user, $amount): CashbookEntry {
            $this->lockBalance($cashbook);

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

            activity()
                ->performedOn($cashbook)
                ->causedBy($user)
                ->event('cashbook.receipt')
                ->withProperties(['amount' => $amount])
                ->log('Manual receipt recorded');

            $this->changeBalance($cashbook, $amount);

            return $entry;
        });
    }

    public function deleteManualReceipt(CashbookEntry $entry): void
    {
        if ($entry->isAutoGenerated()) {
            throw new \LogicException('Auto-generated cashbook entries cannot be deleted.');
        }

        $amount = Money::of($entry->getAttribute('amount'));

        DB::transaction(function () use ($entry, $amount): void {
            $cashbook = $entry->cashbook;
            $this->lockBalance($cashbook);

            // A second delete of the same receipt waits on the cashbook lock above; once the first has
            // committed, the receipt is gone and its amount must not come off the balance again.
            if (! CashbookEntry::whereKey($entry->id)->exists()) {
                return;
            }

            $entry->delete();

            activity()
                ->performedOn($cashbook)
                ->event('cashbook.receipt_deleted')
                ->withProperties(['amount' => $amount])
                ->log('Manual receipt deleted');

            $this->changeBalance($cashbook, bcsub(Money::ZERO, $amount, 2));
        });
    }

    /**
     * Cash can only be paid out of cash on hand: the balance must cover the whole amount, so a zero or
     * negative balance blocks any payout. Money coming in (receipts, refunds) is never blocked.
     *
     * @param Cashbook $cashbook
     * @param numeric-string $amount
     */
    private function coversPayout(Cashbook $cashbook, string $amount): bool
    {
        return bccomp(Money::of($cashbook->balance), $amount, 2) >= 0;
    }

    /**
     * Locks the cashbook row for the rest of the transaction and loads its current balance, so a payout is
     * checked against the balance as it stands and two simultaneous movements cannot both work from the same
     * stale figure.
     *
     * @param Cashbook $cashbook
     */
    private function lockBalance(Cashbook $cashbook): void
    {
        $cashbook->balance = Money::of(Cashbook::whereKey($cashbook->id)->lockForUpdate()->value('balance'));
        $cashbook->syncOriginalAttribute('balance');
    }

    /**
     * Moves the locked cashbook's balance by a signed amount and announces the change.
     *
     * @param Cashbook $cashbook
     * @param numeric-string $change
     */
    private function changeBalance(Cashbook $cashbook, string $change): void
    {
        $previousBalance = Money::of($cashbook->balance);
        $newBalance = bcadd($previousBalance, $change, 2);

        $cashbook->update(['balance' => $newBalance]);

        CashbookBalanceChanged::dispatch($cashbook, $previousBalance, $newBalance);
    }
}
