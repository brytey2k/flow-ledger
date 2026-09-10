<?php

declare(strict_types=1);

namespace Database\Factories\Tenant;

use App\Models\Tenant\Branch;
use App\Models\Tenant\Currency;
use App\Models\Tenant\PaymentRequest;
use App\Models\Tenant\Staff;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PaymentRequest>
 */
class PaymentRequestFactory extends Factory
{
    protected $model = PaymentRequest::class;

    public function definition(): array
    {
        return [
            'staff_id' => Staff::factory(),
            'branch_id' => Branch::factory(),
            'currency_id' => Currency::factory(),
            'type' => fake()->randomElement([\App\Enums\Tenant\PaymentRequestType::Advance->value, \App\Enums\Tenant\PaymentRequestType::Expense->value]),
            'status' => 'draft',
            'total_amount' => fake()->randomFloat(2, 100, 10000),
            'notes' => null,
        ];
    }

    public function advance(): static
    {
        return $this->state(['type' => \App\Enums\Tenant\PaymentRequestType::Advance->value]);
    }

    public function expense(): static
    {
        return $this->state(['type' => \App\Enums\Tenant\PaymentRequestType::Expense->value]);
    }

    public function submitted(): static
    {
        return $this->state(['status' => 'submitted', 'submitted_at' => now()]);
    }

    public function inWorkflow(): static
    {
        return $this->state(['status' => 'in_workflow', 'submitted_at' => now()]);
    }
}
