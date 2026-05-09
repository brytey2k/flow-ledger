<?php

declare(strict_types=1);

namespace Database\Factories\Tenant;

use App\Models\Tenant\Branch;
use App\Models\Tenant\Department;
use App\Models\Tenant\Position;
use App\Models\Tenant\Staff;
use App\Models\Tenant\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Staff>
 */
class StaffFactory extends Factory
{
    protected $model = Staff::class;

    public function definition(): array
    {
        return [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'department_id' => Department::factory(),
            'position_id' => Position::factory(),
            'user_id' => null,
            'branch_id' => null,
        ];
    }

    public function withUser(User|null $user = null): static
    {
        return $this->state(['user_id' => $user ?? User::factory()]);
    }

    public function withBranch(Branch|null $branch = null): static
    {
        return $this->state(['branch_id' => $branch ?? Branch::factory()]);
    }
}
