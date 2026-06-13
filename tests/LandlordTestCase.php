<?php

declare(strict_types=1);

namespace Tests;

use App\Models\Landlord\User;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Str;

abstract class LandlordTestCase extends BaseTestCase
{
    use DatabaseTransactions;

    protected User $landlordUser;

    protected Tenant $tenant;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        $this->landlordUser = User::factory()->create();

        // Create a tenant record without triggering DB-creation events
        $this->tenant = new Tenant([
            'id' => 'landlord-test-' . Str::random(8),
            'name' => 'Landlord Test Tenant',
            'is_suspended' => false,
        ]);
        $this->tenant->saveQuietly();
    }
}
