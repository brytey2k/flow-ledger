<?php

declare(strict_types=1);

namespace Database\Factories\Tenant;

use App\Models\Tenant\Level;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Level>
 */
class LevelFactory extends Factory
{
    protected $model = Level::class;

    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true),
            'position' => fake()->numberBetween(1, 100),
        ];
    }
}
