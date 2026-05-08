<?php

declare(strict_types=1);

namespace Database\Factories\Tenant;

use App\Models\Tenant\WorkflowStage;
use App\Models\Tenant\WorkflowTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WorkflowStage>
 */
class WorkflowStageFactory extends Factory
{
    protected $model = WorkflowStage::class;

    public function definition(): array
    {
        return [
            'workflow_template_id' => WorkflowTemplate::factory(),
            'parallel_group_id' => null,
            'name' => fake()->words(2, true),
            'display_order' => fake()->numberBetween(1, 10),
            'skip_below_amount' => null,
        ];
    }

    public function withThreshold(float $amount): static
    {
        return $this->state(['skip_below_amount' => $amount]);
    }
}
