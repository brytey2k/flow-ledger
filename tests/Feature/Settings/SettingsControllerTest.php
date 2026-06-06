<?php

declare(strict_types=1);

namespace Tests\Feature\Settings;

use App\Enums\Tenant\PermissionKey;
use App\Models\Tenant\CostCode;
use App\Models\Tenant\Currency;
use App\Models\Tenant\Setting;
use App\Models\Tenant\Staff;
use App\Services\SettingsService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TenantAppTestCase;

class SettingsControllerTest extends TenantAppTestCase
{
    // ── Authentication ─────────────────────────────────────────────────────────

    public function test_guest_is_redirected_from_settings(): void
    {
        $this->get(route('settings.index'))->assertRedirect(route('login'));
    }

    public function test_guest_cannot_update_settings(): void
    {
        $this->put(route('settings.update'), [])->assertRedirect(route('login'));
    }

    // ── Authorization ──────────────────────────────────────────────────────────

    public function test_user_without_access_permission_cannot_view_settings(): void
    {
        $this->role->revokePermissionTo(PermissionKey::AccessSettings->value);

        $this->actingAs($this->user)->get(route('settings.index'))->assertForbidden();
    }

    public function test_user_without_access_permission_cannot_update_settings(): void
    {
        $this->role->revokePermissionTo(PermissionKey::AccessSettings->value);

        $this->actingAs($this->user)->put(route('settings.update'), [])->assertForbidden();
    }

    // ── Index ──────────────────────────────────────────────────────────────────

    public function test_authorised_user_can_view_settings_page(): void
    {
        $response = $this->actingAs($this->user)->get(route('settings.index'));

        $response->assertOk();
        $response->assertViewIs('tenant.settings.index');
        $response->assertViewHas('logoUrl');
        $response->assertViewHas('costCodes');
        $response->assertViewHas('defaultAdvanceCostCodeId');
    }

    public function test_settings_page_shows_default_advance_cost_code_preselected(): void
    {
        $costCode = CostCode::factory()->create();
        app(SettingsService::class)->setDefaultAdvanceCostCode($costCode->id);

        $response = $this->actingAs($this->user)->get(route('settings.index'));

        $response->assertOk();
        $response->assertViewHas('defaultAdvanceCostCodeId', $costCode->id);
    }

    // ── Logo upload ────────────────────────────────────────────────────────────

    public function test_authorised_user_can_upload_logo(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('logo.png', 200, 50);

        $response = $this->actingAs($this->user)->put(route('settings.update'), [
            'logo' => $file,
        ]);

        $response->assertRedirect(route('settings.index'));
        $this->assertDatabaseHas('settings', ['key' => 'logo']);

        $setting = Setting::query()->where('key', 'logo')->first();
        $this->assertNotNull($setting);
        Storage::disk('public')->assertExists($setting->value['path']);
    }

    public function test_uploading_new_logo_replaces_old_file(): void
    {
        Storage::fake('public');

        $service = app(SettingsService::class);

        $first = UploadedFile::fake()->image('first.png');
        $service->storeLogo($first);

        $firstSetting = Setting::query()->where('key', 'logo')->first();
        $firstPath = $firstSetting->value['path'];
        Storage::disk('public')->assertExists($firstPath);

        $second = UploadedFile::fake()->image('second.png');
        $this->actingAs($this->user)->put(route('settings.update'), ['logo' => $second]);

        Storage::disk('public')->assertMissing($firstPath);
    }

    public function test_authorised_user_can_remove_logo(): void
    {
        Storage::fake('public');

        $service = app(SettingsService::class);
        $service->storeLogo(UploadedFile::fake()->image('logo.png'));

        $this->assertNotNull($service->getLogoUrl());

        $response = $this->actingAs($this->user)->put(route('settings.update'), [
            'remove_logo' => '1',
        ]);

        $response->assertRedirect(route('settings.index'));
        $this->assertNull($service->getLogoUrl());
    }

    // ── Default advance cost code ──────────────────────────────────────────────

    public function test_authorised_user_can_set_default_advance_cost_code(): void
    {
        $costCode = CostCode::factory()->create();

        $response = $this->actingAs($this->user)->put(route('settings.update'), [
            'default_advance_cost_code_id' => $costCode->id,
        ]);

        $response->assertRedirect(route('settings.index'));
        $this->assertDatabaseHas('settings', ['key' => 'default_advance_cost_code']);

        $service = app(SettingsService::class);
        $this->assertSame($costCode->id, $service->getDefaultAdvanceCostCodeId());
    }

    public function test_authorised_user_can_clear_default_advance_cost_code(): void
    {
        $costCode = CostCode::factory()->create();
        app(SettingsService::class)->setDefaultAdvanceCostCode($costCode->id);

        $this->actingAs($this->user)->put(route('settings.update'), [
            'default_advance_cost_code_id' => '',
        ]);

        $this->assertNull(app(SettingsService::class)->getDefaultAdvanceCostCodeId());
    }

    public function test_default_advance_cost_code_is_applied_when_creating_advance_without_cost_code(): void
    {
        $costCode = CostCode::factory()->create();
        app(SettingsService::class)->setDefaultAdvanceCostCode($costCode->id);

        Staff::factory()->withUser($this->user)->withBranch($this->branch)->create();

        $this->actingAs($this->user)->post(route('payment-requests.store'), [
            'currency_id' => Currency::factory()->create()->id,
            'type' => 'advance',
            'notes' => null,
            'items' => [
                ['description' => 'Travel allowance', 'amount' => '500.00'],
            ],
        ]);

        $this->assertDatabaseHas('payment_request_items', [
            'cost_code_id' => $costCode->id,
        ]);
    }

    public function test_default_advance_cost_code_is_not_applied_when_cost_code_is_explicitly_set(): void
    {
        $defaultCostCode = CostCode::factory()->create();
        $explicitCostCode = CostCode::factory()->create();
        app(SettingsService::class)->setDefaultAdvanceCostCode($defaultCostCode->id);

        Staff::factory()->withUser($this->user)->withBranch($this->branch)->create();

        $this->actingAs($this->user)->post(route('payment-requests.store'), [
            'currency_id' => Currency::factory()->create()->id,
            'type' => 'advance',
            'notes' => null,
            'items' => [
                ['description' => 'Travel allowance', 'amount' => '500.00', 'cost_code_id' => $explicitCostCode->id],
            ],
        ]);

        $this->assertDatabaseHas('payment_request_items', [
            'cost_code_id' => $explicitCostCode->id,
        ]);
        $this->assertDatabaseMissing('payment_request_items', [
            'cost_code_id' => $defaultCostCode->id,
        ]);
    }
}
