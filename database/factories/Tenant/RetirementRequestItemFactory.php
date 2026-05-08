<?php

declare(strict_types=1);

namespace Database\Factories\Tenant;

use App\Models\Tenant\AccountCode;
use App\Models\Tenant\RetirementRequest;
use App\Models\Tenant\RetirementRequestItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RetirementRequestItem>
 */
class RetirementRequestItemFactory extends Factory
{
    protected $model = RetirementRequestItem::class;

    public function definition(): array
    {
        return [
            'retirement_request_id' => RetirementRequest::factory(),
            'account_code_id' => AccountCode::factory(),
            'description' => fake()->sentence(4),
            'amount' => fake()->randomFloat(2, 10, 2000),
            'receipt_number' => null,
        ];
    }
}
