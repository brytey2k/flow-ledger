<?php

declare(strict_types=1);

use App\Support\Deployment\DatabaseRole;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

uses(TestCase::class);

test('reads the privileges of the role the app connects as', function () {
    $role = app(DatabaseRole::class)->privileges();

    expect($role)->toBeArray()
        ->and($role['name'] ?? null)->toBe(DB::connection(config()->string('tenancy.database.central_connection'))->selectOne('select current_user as name')->name)
        ->and($role['createdb'] ?? null)->toBeTrue();
});

test('reports an unreachable database as null', function () {
    config([
        'database.connections.unreachable' => [...config()->array('database.connections.' . config()->string('tenancy.database.central_connection')), 'port' => 1],
        'tenancy.database.central_connection' => 'unreachable',
    ]);

    expect(app(DatabaseRole::class)->privileges())->toBeNull();
});
