<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\Tenant\CashCountDto;
use App\Models\Tenant\Cashbook;
use App\Models\Tenant\CashCount;
use App\Models\Tenant\CurrencyDenomination;
use App\Models\Tenant\User;
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

            $countedTotal = 0.0;
            foreach ($dto->items as $item) {
                $denomination = $denominations->get($item['denomination_id']);
                if ($denomination !== null) {
                    $countedTotal += $item['quantity'] * (float) $denomination->value;
                }
            }

            $balanceAtCount = (float) $cashbook->getAttribute('balance');
            $difference = $countedTotal - $balanceAtCount;

            $cashCount = CashCount::create([
                'cashbook_id' => $cashbook->id,
                'counted_by_user_id' => $user->id,
                'counted_at' => now(),
                'cashbook_balance_at_count' => $balanceAtCount,
                'counted_total' => round($countedTotal, 2),
                'difference' => round($difference, 2),
                'notes' => $dto->notes,
            ]);

            $itemRows = [];
            foreach ($dto->items as $item) {
                $denomination = $denominations->get($item['denomination_id']);
                if ($denomination === null) {
                    continue;
                }
                $subtotal = round($item['quantity'] * (float) $denomination->value, 2);
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
}
