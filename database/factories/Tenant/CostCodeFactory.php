<?php

declare(strict_types=1);

namespace Database\Factories\Tenant;

use App\Models\Tenant\CostCode;
use App\Models\Tenant\Department;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CostCode>
 */
class CostCodeFactory extends Factory
{
    protected $model = CostCode::class;

    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->bothify('??-####')),
            'name' => fake()->words(3, true),
            'department_id' => Department::factory(),
        ];
    }
}
