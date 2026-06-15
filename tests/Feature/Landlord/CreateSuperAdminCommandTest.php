<?php

declare(strict_types=1);

namespace Tests\Feature\Landlord;

use App\Models\Landlord\User;
use Tests\LandlordTestCase;

class CreateSuperAdminCommandTest extends LandlordTestCase
{
    public function test_creates_super_admin_with_valid_input(): void
    {
        $this->artisan('app:create-super-admin')
            ->expectsQuestion('Name', 'Jane Doe')
            ->expectsQuestion('Email address', 'jane@example.com')
            ->expectsQuestion('Password', 'secret123')
            ->expectsOutputToContain('Super admin created successfully.')
            ->expectsOutputToContain('jane@example.com')
            ->assertExitCode(0);

        $this->assertDatabaseHas('landlord_users', [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
        ]);
    }

    public function test_fails_when_email_already_exists(): void
    {
        User::factory()->create(['email' => 'existing@example.com']);

        $this->artisan('app:create-super-admin')
            ->expectsQuestion('Name', 'John Doe')
            ->expectsQuestion('Email address', 'existing@example.com')
            ->expectsQuestion('Password', 'secret123')
            ->expectsOutputToContain('already exists')
            ->assertExitCode(1);
    }
}
