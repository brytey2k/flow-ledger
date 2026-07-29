<?php

declare(strict_types=1);

uses(Tests\ApiTenantTestCase::class);
test('returns authenticated user profile', function () {
    $response = $this->getJson('/api/me')->assertOk();

    $response->assertJsonStructure([
        'data' => [
            'id',
            'first_name',
            'last_name',
            'email',
            'locale',
            'branch',
            'operational_branch',
            'staff_profile',
            'roles',
            'permissions',
        ],
    ]);

    expect($response->json('data.id'))->toBe($this->user->id);
    expect($response->json('data.email'))->toBe($this->user->email);
});
test('branch id matches user branch', function () {
    $response = $this->getJson('/api/me')->assertOk();

    expect($response->json('data.branch.id'))->toBe($this->user->branch_id);
});
test('permissions array is not empty for test user', function () {
    $response = $this->getJson('/api/me')->assertOk();

    expect($response->json('data.permissions'))->not->toBeEmpty();
});
test('roles contains test role', function () {
    $response = $this->getJson('/api/me')->assertOk();

    expect($response->json('data.roles'))->toContain($this->role->name);
});
