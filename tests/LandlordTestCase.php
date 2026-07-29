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

    // Public so Pest's file-local helper functions (which run outside any class scope) can
    // read these fixtures via test()->landlordUser, test()->tenant.
    public User $landlordUser;

    public Tenant $tenant;

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
