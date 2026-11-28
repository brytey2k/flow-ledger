<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\Tenant\CashCountDto;
use App\Models\Tenant\Cashbook;
use App\Models\Tenant\CashCount;
use App\Models\Tenant\CurrencyDenomination;
use App\Models\Tenant\User;
use App\Support\Money;
use Illuminate\Support\Facades\DB;

class CashCountService
{
    public function store(Cashbook $cashbook, CashCountDto $dto, User $user): CashCount
    {
        return DB::transaction(function () use ($cashbook, $dto, $user): CashCount {
            $denominationIds = array_column($dto->items, 'denomination_id');
            $denominations = CurrencyDenomination::whereIn('id', $denominationIds)
                ->get()
                ->keyBy('id');

            $countedTotal = Money::ZERO;
            foreach ($dto->items as $item) {
                $denomination = $denominations->get($item['denomination_id']);
                if ($denomination !== null) {
                    $countedTotal = bcadd($countedTotal, $this->subtotal($item['quantity'], $denomination), 2);
                }
            }

            $balanceAtCount = Money::of($cashbook->balance);

            $cashCount = CashCount::create([
                'cashbook_id' => $cashbook->id,
                'counted_by_user_id' => $user->id,
                'counted_at' => now(),
                'cashbook_balance_at_count' => $balanceAtCount,
                'counted_total' => $countedTotal,
                'difference' => bcsub($countedTotal, $balanceAtCount, 2),
                'notes' => $dto->notes,
            ]);

            $itemRows = [];
            foreach ($dto->items as $item) {
                $denomination = $denominations->get($item['denomination_id']);
                if ($denomination === null) {
                    continue;
                }
                $subtotal = $this->subtotal($item['quantity'], $denomination);
                $itemRows[] = [
                    'cash_count_id' => $cashCount->id,
                    'denomination_id' => $denomination->id,
                    'denomination_value' => $denomination->value,
                    'denomination_label' => $denomination->label,
                    'quantity' => $item['quantity'],
                    'subtotal' => $subtotal,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            if (! empty($itemRows)) {
                \App\Models\Tenant\CashCountItem::insert($itemRows);
            }

            activity()
                ->performedOn($cashCount)
                ->causedBy($user)
                ->event('cash_count.created')
                ->withProperties([
                    'total' => $cashCount->counted_total,
                    'balance_at_count' => $cashCount->cashbook_balance_at_count,
                    'difference' => $cashCount->difference,
                ])
                ->log('Cash count recorded');

            return $cashCount;
        });
    }

    public function delete(CashCount $cashCount, User $user): void
    {
        $cashCount->delete();

        activity()
            ->performedOn($cashCount)
            ->causedBy($user)
            ->event('cash_count.deleted')
            ->withProperties(['cash_count_id' => $cashCount->id])
            ->log('Cash count deleted');
    }

    /**
     * The value of a stack of one denomination, to the cent. Denominations carry four decimal places, so the
     * product is rounded half up rather than truncated.
     *
     * @param int $quantity
     * @param CurrencyDenomination $denomination
     *
     * @return numeric-string
     */
    private function subtotal(int $quantity, CurrencyDenomination $denomination): string
    {
        $value = $denomination->value;
        assert(is_numeric($value));

        return Money::of(bcround(bcmul((string) $quantity, $value, 4), 2));
    }
}
