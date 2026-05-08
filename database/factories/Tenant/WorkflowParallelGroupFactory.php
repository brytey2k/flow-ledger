<?php

declare(strict_types=1);

namespace Database\Factories\Tenant;

use App\Models\Tenant\WorkflowParallelGroup;
use App\Models\Tenant\WorkflowTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WorkflowParallelGroup>
 */
class WorkflowParallelGroupFactory extends Factory
{
    protected $model = WorkflowParallelGroup::class;

    public function definition(): array
    {
        return [
            'workflow_template_id' => WorkflowTemplate::factory(),
            'name' => fake()->words(2, true),
            'require_all' => fake()->boolean(),
        ];
    }

    public function requireAll(): static
    {
        return $this->state(['require_all' => true]);
    }

    public function requireAny(): static
    {
        return $this->state(['require_all' => false]);
    }
}
