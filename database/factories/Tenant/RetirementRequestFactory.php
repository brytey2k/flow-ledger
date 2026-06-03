<?php

declare(strict_types=1);

namespace Database\Factories\Tenant;

use App\Models\Tenant\PaymentRequest;
use App\Models\Tenant\RetirementRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RetirementRequest>
 */
class RetirementRequestFactory extends Factory
{
    protected $model = RetirementRequest::class;

    public function definition(): array
    {
        return [
            'payment_request_id' => PaymentRequest::factory()->advance()->state([
                'status' => 'disbursed',
                'disbursed_at' => now(),
            ]),
            'status' => 'draft',
            'total_amount_expended' => fake()->randomFloat(2, 50, 10000),
            'difference_amount' => 0,
            'difference_type' => 'nil',
            'notes' => null,
            'no_money_spent' => false,
        ];
    }

    public function submitted(): static
    {
        return $this->state(['status' => 'submitted', 'submitted_at' => now()]);
    }

    public function inWorkflow(): static
    {
        return $this->state(['status' => 'in_workflow', 'submitted_at' => now()]);
    }

    public function approved(): static
    {
        return $this->state(['status' => 'approved', 'submitted_at' => now(), 'approved_at' => now()]);
    }
}
