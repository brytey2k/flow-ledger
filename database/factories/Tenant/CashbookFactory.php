<?php

declare(strict_types=1);

namespace Database\Factories\Tenant;

use App\Models\Tenant\Branch;
use App\Models\Tenant\Cashbook;
use App\Models\Tenant\Currency;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Cashbook>
 */
class CashbookFactory extends Factory
{
    protected $model = Cashbook::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'branch_id' => Branch::factory(),
            'currency_id' => Currency::factory(),
            'balance' => 0,
        ];
    }

    /**
     * A cashbook holding enough cash for any payout a test makes.
     *
     * @param float $balance
     */
    public function funded(float $balance = 1_000_000.00): static
    {
        return $this->state(['balance' => $balance]);
    }
}
