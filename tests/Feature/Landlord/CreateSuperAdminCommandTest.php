<?php

declare(strict_types=1);

uses(Tests\LandlordTestCase::class);
use App\Models\Landlord\User;

test('creates super admin with valid input', function () {
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
});
test('fails when email already exists', function () {
    User::factory()->create(['email' => 'existing@example.com']);

    $this->artisan('app:create-super-admin')
        ->expectsQuestion('Name', 'John Doe')
        ->expectsQuestion('Email address', 'existing@example.com')
        ->expectsQuestion('Password', 'secret123')
        ->expectsOutputToContain('already exists')
        ->assertExitCode(1);
});
