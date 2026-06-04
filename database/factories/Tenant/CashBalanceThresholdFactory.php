<?php

declare(strict_types=1);

namespace Database\Factories\Tenant;

use App\Models\Tenant\Branch;
use App\Models\Tenant\CashBalanceThreshold;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CashBalanceThreshold>
 */
class CashBalanceThresholdFactory extends Factory
{
    protected $model = CashBalanceThreshold::class;

    public function definition(): array
    {
        return [
            'branch_id' => Branch::factory(),
            'threshold_amount' => fake()->randomFloat(2, 1000, 100000),
            'notification_user_ids' => [],
            'cooldown_minutes' => 1440,
            'is_active' => true,
        ];
    }
}
