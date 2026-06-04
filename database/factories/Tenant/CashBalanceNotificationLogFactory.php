<?php

declare(strict_types=1);

namespace Database\Factories\Tenant;

use App\Models\Tenant\CashBalanceNotificationLog;
use App\Models\Tenant\CashBalanceThreshold;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CashBalanceNotificationLog>
 */
class CashBalanceNotificationLogFactory extends Factory
{
    protected $model = CashBalanceNotificationLog::class;

    public function definition(): array
    {
        return [
            'cash_balance_threshold_id' => CashBalanceThreshold::factory(),
            'balance_amount' => fake()->randomFloat(2, 0, 100000),
            'notified_at' => now(),
        ];
    }
}
