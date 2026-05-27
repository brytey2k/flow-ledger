<?php

declare(strict_types=1);

namespace Database\Factories\Tenant;

use App\Models\Tenant\WorkflowTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WorkflowTemplate>
 */
class WorkflowTemplateFactory extends Factory
{
    protected $model = WorkflowTemplate::class;

    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'type' => fake()->randomElement([\App\Enums\Tenant\PaymentRequestType::Advance->value, \App\Enums\Tenant\PaymentRequestType::Expense->value, \App\Enums\Tenant\PaymentRequestType::Retirement->value]),
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

    public function retirement(): static
    {
        return $this->state(['type' => \App\Enums\Tenant\PaymentRequestType::Retirement->value]);
    }
}
