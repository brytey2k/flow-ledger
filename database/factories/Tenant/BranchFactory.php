<?php

declare(strict_types=1);

namespace Database\Factories\Tenant;

use App\Models\Tenant\Branch;
use App\Models\Tenant\Level;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Branch>
 */
class BranchFactory extends Factory
{
    protected $model = Branch::class;

    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'code' => fake()->unique()->bothify('???-####'),
            'level_id' => Level::factory(),
            'parent_id' => null,
            'position' => fake()->numberBetween(1, 100),
        ];
    }

    public function withParent(Branch|int|null $parent = null): static
    {
        return $this->state(function () use ($parent): array {
            if ($parent === null) {
                return ['parent_id' => Branch::factory()];
            }

            return ['parent_id' => $parent instanceof Branch ? $parent->id : $parent];
        });
    }
}
