<?php

declare(strict_types=1);

namespace Database\Factories\Tenant;

use App\Models\Tenant\Position;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Position>
 */
class PositionFactory extends Factory
{
    protected $model = Position::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->jobTitle(),
        ];
    }
}
