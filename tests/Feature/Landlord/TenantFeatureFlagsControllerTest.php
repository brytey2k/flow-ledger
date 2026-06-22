<?php

declare(strict_types=1);

namespace Tests\Feature\Landlord;

use App\Enums\FeatureFlag;
use Tests\LandlordTestCase;

class TenantFeatureFlagsControllerTest extends LandlordTestCase
{
    // ── Overview ──────────────────────────────────────────────────────────────

    public function test_overview_renders_for_authenticated_user(): void
    {
        $this->actingAs($this->landlordUser, 'landlord')
            ->get(route('landlord.feature-flags.index'))
            ->assertOk()
            ->assertViewHas('tenants')
            ->assertViewHas('flagDefinitions');
    }

    // ── Per-tenant flag index ─────────────────────────────────────────────────

    public function test_index_renders_flags_for_tenant(): void
    {
        $this->actingAs($this->landlordUser, 'landlord')
            ->get(route('landlord.tenants.feature-flags.index', $this->tenant))
            ->assertOk()
            ->assertViewHas('flags')
            ->assertViewHas('flagDefinitions');
    }

    // ── Update ────────────────────────────────────────────────────────────────

    public function test_update_activates_selected_flags(): void
    {
        $this->actingAs($this->landlordUser, 'landlord')
            ->put(route('landlord.tenants.feature-flags.update', $this->tenant), [
                'flags' => [FeatureFlag::LocalAuth->value],
            ])
            ->assertRedirect()
            ->assertSessionHas('success');
    }

    public function test_update_with_no_flags_deactivates_all(): void
    {
        $this->actingAs($this->landlordUser, 'landlord')
            ->put(route('landlord.tenants.feature-flags.update', $this->tenant), [
                'flags' => [],
            ])
            ->assertRedirect()
            ->assertSessionHas('success');
    }

    // ── Bulk update ───────────────────────────────────────────────────────────

    public function test_bulk_update_enables_flag_for_all_tenants(): void
    {
        $this->actingAs($this->landlordUser, 'landlord')
            ->post(route('landlord.feature-flags.bulk-update'), [
                'flag' => FeatureFlag::LocalAuth->value,
                'action' => 'enable',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');
    }

    public function test_bulk_update_disables_flag_for_all_tenants(): void
    {
        $this->actingAs($this->landlordUser, 'landlord')
            ->post(route('landlord.feature-flags.bulk-update'), [
                'flag' => FeatureFlag::LocalAuth->value,
                'action' => 'disable',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');
    }
}
