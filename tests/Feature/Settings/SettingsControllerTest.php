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
        $response->assertViewHas('lightLogoUrl');
        $response->assertViewHas('darkLogoUrl');
        $response->assertViewHas('smallLogoUrl');
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

    public function test_authorised_user_can_upload_light_logo(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('logo_light.png', 200, 50);

        $response = $this->actingAs($this->user)->put(route('settings.update'), [
            'logo_light' => $file,
        ]);

        $response->assertRedirect(route('settings.index'));
        $this->assertDatabaseHas('settings', ['key' => 'logo']);

        $setting = Setting::query()->where('key', 'logo')->first();
        $this->assertNotNull($setting);
        Storage::disk('public')->assertExists($setting->value['path']);
    }

    public function test_uploading_new_light_logo_replaces_old_file(): void
    {
        Storage::fake('public');

        $service = app(SettingsService::class);

        $first = UploadedFile::fake()->image('first.png');
        $service->storeLightLogo($first);

        $firstSetting = Setting::query()->where('key', 'logo')->first();
        $firstPath = $firstSetting->value['path'];
        Storage::disk('public')->assertExists($firstPath);

        $second = UploadedFile::fake()->image('second.png');
        $this->actingAs($this->user)->put(route('settings.update'), ['logo_light' => $second]);

        Storage::disk('public')->assertMissing($firstPath);
    }

    public function test_authorised_user_can_remove_light_logo(): void
    {
        Storage::fake('public');

        $service = app(SettingsService::class);
        $service->storeLightLogo(UploadedFile::fake()->image('logo.png'));

        $this->assertNotNull($service->getLightLogoUrl());

        $response = $this->actingAs($this->user)->put(route('settings.update'), [
            'remove_logo_light' => '1',
        ]);

        $response->assertRedirect(route('settings.index'));
        $this->assertNull($service->getLightLogoUrl());
    }

    public function test_authorised_user_can_upload_dark_logo(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('logo_dark.png', 200, 50);

        $response = $this->actingAs($this->user)->put(route('settings.update'), [
            'logo_dark' => $file,
        ]);

        $response->assertRedirect(route('settings.index'));
        $this->assertDatabaseHas('settings', ['key' => 'logo_dark']);

        $setting = Setting::query()->where('key', 'logo_dark')->first();
        $this->assertNotNull($setting);
        Storage::disk('public')->assertExists($setting->value['path']);
    }

    public function test_uploading_new_dark_logo_replaces_old_file(): void
    {
        Storage::fake('public');

        $service = app(SettingsService::class);

        $first = UploadedFile::fake()->image('first_dark.png');
        $service->storeDarkLogo($first);

        $firstSetting = Setting::query()->where('key', 'logo_dark')->first();
        $firstPath = $firstSetting->value['path'];
        Storage::disk('public')->assertExists($firstPath);

        $second = UploadedFile::fake()->image('second_dark.png');
        $this->actingAs($this->user)->put(route('settings.update'), ['logo_dark' => $second]);

        Storage::disk('public')->assertMissing($firstPath);
    }

    public function test_authorised_user_can_remove_dark_logo(): void
    {
        Storage::fake('public');

        $service = app(SettingsService::class);
        $service->storeDarkLogo(UploadedFile::fake()->image('logo_dark.png'));

        $this->assertNotNull($service->getDarkLogoUrl());

        $response = $this->actingAs($this->user)->put(route('settings.update'), [
            'remove_logo_dark' => '1',
        ]);

        $response->assertRedirect(route('settings.index'));
        $this->assertNull($service->getDarkLogoUrl());
    }

    public function test_authorised_user_can_upload_small_logo(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('logo_small.png', 44, 44);

        $response = $this->actingAs($this->user)->put(route('settings.update'), [
            'logo_small' => $file,
        ]);

        $response->assertRedirect(route('settings.index'));
        $this->assertDatabaseHas('settings', ['key' => 'logo_small']);

        $setting = Setting::query()->where('key', 'logo_small')->first();
        $this->assertNotNull($setting);
        Storage::disk('public')->assertExists($setting->value['path']);
    }

    public function test_uploading_new_small_logo_replaces_old_file(): void
    {
        Storage::fake('public');

        $service = app(SettingsService::class);

        $first = UploadedFile::fake()->image('first_small.png');
        $service->storeSmallLogo($first);

        $firstSetting = Setting::query()->where('key', 'logo_small')->first();
        $firstPath = $firstSetting->value['path'];
        Storage::disk('public')->assertExists($firstPath);

        $second = UploadedFile::fake()->image('second_small.png');
        $this->actingAs($this->user)->put(route('settings.update'), ['logo_small' => $second]);

        Storage::disk('public')->assertMissing($firstPath);
    }

    public function test_authorised_user_can_remove_small_logo(): void
    {
        Storage::fake('public');

        $service = app(SettingsService::class);
        $service->storeSmallLogo(UploadedFile::fake()->image('logo_small.png'));

        $this->assertNotNull($service->getSmallLogoUrl());

        $response = $this->actingAs($this->user)->put(route('settings.update'), [
            'remove_logo_small' => '1',
        ]);

        $response->assertRedirect(route('settings.index'));
        $this->assertNull($service->getSmallLogoUrl());
    }

    public function test_light_logo_upload_does_not_affect_dark_or_small(): void
    {
        Storage::fake('public');

        $service = app(SettingsService::class);
        $service->storeDarkLogo(UploadedFile::fake()->image('dark.png'));
        $service->storeSmallLogo(UploadedFile::fake()->image('small.png'));

        $darkPath = Setting::query()->where('key', 'logo_dark')->first()->value['path'];
        $smallPath = Setting::query()->where('key', 'logo_small')->first()->value['path'];

        $this->actingAs($this->user)->put(route('settings.update'), [
            'logo_light' => UploadedFile::fake()->image('new_light.png'),
        ]);

        Storage::disk('public')->assertExists($darkPath);
        Storage::disk('public')->assertExists($smallPath);
    }

    public function test_settings_page_passes_all_three_logo_urls_to_view(): void
    {
        Storage::fake('public');

        $service = app(SettingsService::class);
        $service->storeLightLogo(UploadedFile::fake()->image('light.png'));
        $service->storeDarkLogo(UploadedFile::fake()->image('dark.png'));
        $service->storeSmallLogo(UploadedFile::fake()->image('small.png'));

        $response = $this->actingAs($this->user)->get(route('settings.index'));

        $response->assertOk();
        $response->assertViewHas('lightLogoUrl', $service->getLightLogoUrl());
        $response->assertViewHas('darkLogoUrl', $service->getDarkLogoUrl());
        $response->assertViewHas('smallLogoUrl', $service->getSmallLogoUrl());
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
