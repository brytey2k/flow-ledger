<?php

declare(strict_types=1);

uses(Tests\ApiTenantTestCase::class);
use App\Interfaces\SessionInvalidatorInterface;
use Illuminate\Support\Facades\Cache;
use Mockery\MockInterface;

test('logout invalidates session for authenticated user', function () {
    $this->mock(SessionInvalidatorInterface::class, function (MockInterface $mock): void {
        $mock->shouldReceive('invalidate')
            ->once()
            ->with($this->user->id);
    });

    $this->postJson('/api/logout')
        ->assertOk()
        ->assertJsonPath('message', 'Logged out.');
});
test('logout sets force logout flag end to end', function () {
    $this->postJson('/api/logout')->assertOk();

    expect((bool) Cache::get("force_logout:{$this->user->id}"))->toBeTrue();
});
