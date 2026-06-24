<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Tenant;

use Tests\ApiTenantTestCase;

class MeControllerTest extends ApiTenantTestCase
{
    public function test_returns_authenticated_user_profile(): void
    {
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

        $this->assertSame($this->user->id, $response->json('data.id'));
        $this->assertSame($this->user->email, $response->json('data.email'));
    }

    public function test_branch_id_matches_user_branch(): void
    {
        $response = $this->getJson('/api/me')->assertOk();

        $this->assertSame(
            $this->user->branch_id,
            $response->json('data.branch.id'),
        );
    }

    public function test_permissions_array_is_not_empty_for_test_user(): void
    {
        $response = $this->getJson('/api/me')->assertOk();

        $this->assertNotEmpty($response->json('data.permissions'));
    }

    public function test_roles_contains_test_role(): void
    {
        $response = $this->getJson('/api/me')->assertOk();

        $this->assertContains($this->role->name, $response->json('data.roles'));
    }

}
