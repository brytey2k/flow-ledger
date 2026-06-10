<?php

declare(strict_types=1);

namespace Database\Factories\Tenant;

use App\Models\Tenant\Cashbook;
use App\Models\Tenant\CashCount;
use App\Models\Tenant\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CashCount>
 */
class CashCountFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $bookBalance = fake()->randomFloat(2, 500, 50000);
        $countedTotal = $bookBalance;

        return [
            'cashbook_id' => Cashbook::factory(),
            'counted_by_user_id' => User::factory(),
            'counted_at' => now(),
            'cashbook_balance_at_count' => $bookBalance,
            'counted_total' => $countedTotal,
            'difference' => round($countedTotal - $bookBalance, 2),
            'notes' => null,
        ];
    }

    public function surplus(): static
    {
        return $this->state(function (): array {
            $bookBalance = fake()->randomFloat(2, 500, 50000);
            $countedTotal = $bookBalance + fake()->randomFloat(2, 1, 500);

            return [
                'cashbook_balance_at_count' => $bookBalance,
                'counted_total' => $countedTotal,
                'difference' => round($countedTotal - $bookBalance, 2),
            ];
        });
    }

    public function deficit(): static
    {
        return $this->state(function (): array {
            $bookBalance = fake()->randomFloat(2, 500, 50000);
            $countedTotal = $bookBalance - fake()->randomFloat(2, 1, 200);

            return [
                'cashbook_balance_at_count' => $bookBalance,
                'counted_total' => $countedTotal,
                'difference' => round($countedTotal - $bookBalance, 2),
            ];
        });
    }
}
