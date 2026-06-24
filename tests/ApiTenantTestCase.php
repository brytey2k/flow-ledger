<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Base class for tenant API endpoint tests.
 * Authenticates via actingAs() on the iam_jwt guard so tests focus on
 * controller/service behaviour rather than JWT validation.
 */
abstract class ApiTenantTestCase extends TenantAppTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs($this->user, 'iam_jwt');
        Auth::shouldUse('iam_jwt');

        // The TenantAppTestCase caches the branch across tests using an in-memory cache.
        // After each test's DatabaseTransactions rollback the branches_tree closure entries
        // are also rolled back, leaving the cached branch without a self-reference row.
        // BranchScopeService uses this table when the user has ViewDescendantBranches, so
        // we re-insert the self-reference for the current test's branch here.
        DB::connection('tenant')->table('branches_tree')->insertOrIgnore([
            'ancestor_id' => $this->branch->id,
            'descendant_id' => $this->branch->id,
            'depth' => 0,
        ]);
    }

    /** Re-authenticate as a different user for the current test. */
    protected function actingAsApiUser(\App\Models\Tenant\User $user): static
    {
        $this->actingAs($user, 'iam_jwt');
        Auth::shouldUse('iam_jwt');

        return $this;
    }
}
