<?php

declare(strict_types=1);

namespace Database\Factories\Tenant;

use App\Models\Tenant\AccountCode;
use App\Models\Tenant\Department;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AccountCode>
 */
class AccountCodeFactory extends Factory
{
    protected $model = AccountCode::class;

    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->bothify('??-####')),
            'name' => fake()->words(3, true),
            'department_id' => Department::factory(),
        ];
    }
}
