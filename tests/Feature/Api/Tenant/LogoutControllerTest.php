<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Tenant;

use App\Interfaces\SessionInvalidatorInterface;
use Illuminate\Support\Facades\Cache;
use Mockery\MockInterface;
use Tests\ApiTenantTestCase;

class LogoutControllerTest extends ApiTenantTestCase
{
    public function test_logout_invalidates_session_for_authenticated_user(): void
    {
        $this->mock(SessionInvalidatorInterface::class, function (MockInterface $mock): void {
            $mock->shouldReceive('invalidate')
                ->once()
                ->with($this->user->id);
        });

        $this->postJson('/api/logout')
            ->assertOk()
            ->assertJsonPath('message', 'Logged out.');
    }

    public function test_logout_sets_force_logout_flag_end_to_end(): void
    {
        $this->postJson('/api/logout')->assertOk();

        $this->assertTrue((bool) Cache::get("force_logout:{$this->user->id}"));
    }
}
