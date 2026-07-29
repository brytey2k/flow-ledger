<?php

declare(strict_types=1);

uses(Tests\TenantAppTestCase::class);
use App\Enums\Tenant\PermissionKey;
use App\Models\Tenant\CostCode;
use App\Models\Tenant\Currency;
use App\Models\Tenant\Setting;
use App\Models\Tenant\Staff;
use App\Services\SettingsService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('guest is redirected from settings', function () {
    $this->get(route('settings.index'))->assertRedirect(route('login'));
});
test('guest cannot update settings', function () {
    $this->put(route('settings.update'), [])->assertRedirect(route('login'));
});
test('user without access permission cannot view settings', function () {
    $this->role->revokePermissionTo(PermissionKey::AccessSettings->value);

    $this->actingAs($this->user)->get(route('settings.index'))->assertForbidden();
});
test('user without access permission cannot update settings', function () {
    $this->role->revokePermissionTo(PermissionKey::AccessSettings->value);

    $this->actingAs($this->user)->put(route('settings.update'), [])->assertForbidden();
});
test('authorised user can view settings page', function () {
    $response = $this->actingAs($this->user)->get(route('settings.index'));

    $response->assertOk();
    $response->assertViewIs('tenant.settings.index');
    $response->assertViewHas('lightLogoUrl');
    $response->assertViewHas('darkLogoUrl');
    $response->assertViewHas('smallLogoUrl');
    $response->assertViewHas('costCodes');
    $response->assertViewHas('defaultAdvanceCostCodeId');
});
test('settings page shows default advance cost code preselected', function () {
    $costCode = CostCode::factory()->create();
    app(SettingsService::class)->setDefaultAdvanceCostCode($costCode->id);

    $response = $this->actingAs($this->user)->get(route('settings.index'));

    $response->assertOk();
    $response->assertViewHas('defaultAdvanceCostCodeId', $costCode->id);
});
test('authorised user can upload light logo', function () {
    Storage::fake('public');

    $file = UploadedFile::fake()->image('logo_light.png', 200, 50);

    $response = $this->actingAs($this->user)->put(route('settings.update'), [
        'logo_light' => $file,
    ]);

    $response->assertRedirect(route('settings.index'));
    $this->assertDatabaseHas('settings', ['key' => 'logo']);

    $setting = Setting::query()->where('key', 'logo')->first();
    expect($setting)->not->toBeNull();
    Storage::disk('public')->assertExists($setting->value['path']);
});
test('uploading new light logo replaces old file', function () {
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
});
test('authorised user can remove light logo', function () {
    Storage::fake('public');

    $service = app(SettingsService::class);
    $service->storeLightLogo(UploadedFile::fake()->image('logo.png'));

    expect($service->getLightLogoUrl())->not->toBeNull();

    $response = $this->actingAs($this->user)->put(route('settings.update'), [
        'remove_logo_light' => '1',
    ]);

    $response->assertRedirect(route('settings.index'));
    expect($service->getLightLogoUrl())->toBeNull();
});
test('authorised user can upload dark logo', function () {
    Storage::fake('public');

    $file = UploadedFile::fake()->image('logo_dark.png', 200, 50);

    $response = $this->actingAs($this->user)->put(route('settings.update'), [
        'logo_dark' => $file,
    ]);

    $response->assertRedirect(route('settings.index'));
    $this->assertDatabaseHas('settings', ['key' => 'logo_dark']);

    $setting = Setting::query()->where('key', 'logo_dark')->first();
    expect($setting)->not->toBeNull();
    Storage::disk('public')->assertExists($setting->value['path']);
});
test('uploading new dark logo replaces old file', function () {
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
});
test('authorised user can remove dark logo', function () {
    Storage::fake('public');

    $service = app(SettingsService::class);
    $service->storeDarkLogo(UploadedFile::fake()->image('logo_dark.png'));

    expect($service->getDarkLogoUrl())->not->toBeNull();

    $response = $this->actingAs($this->user)->put(route('settings.update'), [
        'remove_logo_dark' => '1',
    ]);

    $response->assertRedirect(route('settings.index'));
    expect($service->getDarkLogoUrl())->toBeNull();
});
test('authorised user can upload small logo', function () {
    Storage::fake('public');

    $file = UploadedFile::fake()->image('logo_small.png', 44, 44);

    $response = $this->actingAs($this->user)->put(route('settings.update'), [
        'logo_small' => $file,
    ]);

    $response->assertRedirect(route('settings.index'));
    $this->assertDatabaseHas('settings', ['key' => 'logo_small']);

    $setting = Setting::query()->where('key', 'logo_small')->first();
    expect($setting)->not->toBeNull();
    Storage::disk('public')->assertExists($setting->value['path']);
});
test('uploading new small logo replaces old file', function () {
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
});
test('authorised user can remove small logo', function () {
    Storage::fake('public');

    $service = app(SettingsService::class);
    $service->storeSmallLogo(UploadedFile::fake()->image('logo_small.png'));

    expect($service->getSmallLogoUrl())->not->toBeNull();

    $response = $this->actingAs($this->user)->put(route('settings.update'), [
        'remove_logo_small' => '1',
    ]);

    $response->assertRedirect(route('settings.index'));
    expect($service->getSmallLogoUrl())->toBeNull();
});
test('light logo upload does not affect dark or small', function () {
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
});
test('settings page passes all three logo urls to view', function () {
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
});
test('authorised user can set default advance cost code', function () {
    $costCode = CostCode::factory()->create();

    $response = $this->actingAs($this->user)->put(route('settings.update'), [
        'default_advance_cost_code_id' => $costCode->id,
    ]);

    $response->assertRedirect(route('settings.index'));
    $this->assertDatabaseHas('settings', ['key' => 'default_advance_cost_code']);

    $service = app(SettingsService::class);
    expect($service->getDefaultAdvanceCostCodeId())->toBe($costCode->id);
});
test('authorised user can clear default advance cost code', function () {
    $costCode = CostCode::factory()->create();
    app(SettingsService::class)->setDefaultAdvanceCostCode($costCode->id);

    $this->actingAs($this->user)->put(route('settings.update'), [
        'default_advance_cost_code_id' => '',
    ]);

    expect(app(SettingsService::class)->getDefaultAdvanceCostCodeId())->toBeNull();
});
test('default advance cost code is applied when creating advance without cost code', function () {
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
});
test('default advance cost code is not applied when cost code is explicitly set', function () {
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
});
