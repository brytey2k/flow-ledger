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
            'type' => fake()->randomElement(['advance', 'expense', 'retirement']),
        ];
    }

    public function advance(): static
    {
        return $this->state(['type' => 'advance']);
    }

    public function expense(): static
    {
        return $this->state(['type' => 'expense']);
    }

    public function retirement(): static
    {
        return $this->state(['type' => 'retirement']);
    }
}
