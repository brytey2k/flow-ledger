<?php

declare(strict_types=1);

uses(Tests\TenantAppTestCase::class);
use App\Models\Tenant\CostCode;
use App\Services\SettingsService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->service = app(SettingsService::class);
});
test('get light logo url returns null when not set', function () {
    $result = $this->service->getLightLogoUrl();

    expect($result)->toBeNull();
});
test('get dark logo url returns null when not set', function () {
    $result = $this->service->getDarkLogoUrl();

    expect($result)->toBeNull();
});
test('get small logo url returns null when not set', function () {
    $result = $this->service->getSmallLogoUrl();

    expect($result)->toBeNull();
});
test('store and get light logo url returns route', function () {
    Storage::fake('public');
    $file = UploadedFile::fake()->image('logo.png');

    $this->service->storeLightLogo($file);
    $url = $this->service->getLightLogoUrl();

    expect($url)->not->toBeNull();
    expect($url)->toBeString();
});
test('remove light logo makes url null', function () {
    Storage::fake('public');
    $file = UploadedFile::fake()->image('logo.png');
    $this->service->storeLightLogo($file);

    $this->service->removeLightLogo();
    $url = $this->service->getLightLogoUrl();

    expect($url)->toBeNull();
});
test('store and get dark logo url returns route', function () {
    Storage::fake('public');
    $file = UploadedFile::fake()->image('dark_logo.png');

    $this->service->storeDarkLogo($file);
    $url = $this->service->getDarkLogoUrl();

    expect($url)->not->toBeNull();
    expect($url)->toBeString();
});
test('remove dark logo makes url null', function () {
    Storage::fake('public');
    $file = UploadedFile::fake()->image('dark_logo.png');
    $this->service->storeDarkLogo($file);

    $this->service->removeDarkLogo();
    $url = $this->service->getDarkLogoUrl();

    expect($url)->toBeNull();
});
test('store and get small logo url returns route', function () {
    Storage::fake('public');
    $file = UploadedFile::fake()->image('small_logo.png');

    $this->service->storeSmallLogo($file);
    $url = $this->service->getSmallLogoUrl();

    expect($url)->not->toBeNull();
    expect($url)->toBeString();
});
test('remove small logo makes url null', function () {
    Storage::fake('public');
    $file = UploadedFile::fake()->image('small_logo.png');
    $this->service->storeSmallLogo($file);

    $this->service->removeSmallLogo();
    $url = $this->service->getSmallLogoUrl();

    expect($url)->toBeNull();
});
test('get default advance cost code id returns null when not set', function () {
    $result = $this->service->getDefaultAdvanceCostCodeId();

    expect($result)->toBeNull();
});
test('get default advance cost code returns null when not set', function () {
    $result = $this->service->getDefaultAdvanceCostCode();

    expect($result)->toBeNull();
});
test('set and get default advance cost code', function () {
    $costCode = CostCode::factory()->create();

    $this->service->setDefaultAdvanceCostCode($costCode->id);
    $result = $this->service->getDefaultAdvanceCostCodeId();

    expect($result)->toBe($costCode->id);
});
test('get default advance cost code returns model when set', function () {
    $costCode = CostCode::factory()->create();

    $this->service->setDefaultAdvanceCostCode($costCode->id);
    $result = $this->service->getDefaultAdvanceCostCode();

    expect($result)->toBeInstanceOf(CostCode::class);
    expect($result->id)->toBe($costCode->id);
});
test('set default advance cost code to null', function () {
    $costCode = CostCode::factory()->create();
    $this->service->setDefaultAdvanceCostCode($costCode->id);

    $this->service->setDefaultAdvanceCostCode(null);
    $result = $this->service->getDefaultAdvanceCostCodeId();

    expect($result)->toBeNull();
});
test('changing default advance cost code logs activity', function () {
    $this->actingAs($this->user);
    $costCode = CostCode::factory()->create();

    $this->service->setDefaultAdvanceCostCode($costCode->id);

    $this->assertDatabaseHas('activity_log', [
        'event' => 'settings.updated',
        'causer_id' => $this->user->id,
    ]);
});
test('setting the same value twice does not duplicate activity log', function () {
    $costCode = CostCode::factory()->create();

    $this->service->setDefaultAdvanceCostCode($costCode->id);
    $countAfterFirst = Spatie\Activitylog\Models\Activity::where('event', 'settings.updated')->count();

    $this->service->setDefaultAdvanceCostCode($costCode->id);
    $countAfterSecond = Spatie\Activitylog\Models\Activity::where('event', 'settings.updated')->count();

    expect($countAfterSecond)->toBe($countAfterFirst);
});
test('expense source document required defaults to false', function () {
    $result = $this->service->isExpenseSourceDocumentRequired();

    expect($result)->toBeFalse();
});
test('set require expense source documents to true', function () {
    $this->service->setRequireExpenseSourceDocuments(true);

    expect($this->service->isExpenseSourceDocumentRequired())->toBeTrue();
});
test('set require expense source documents to false', function () {
    $this->service->setRequireExpenseSourceDocuments(true);
    $this->service->setRequireExpenseSourceDocuments(false);

    expect($this->service->isExpenseSourceDocumentRequired())->toBeFalse();
});
test('retirement source document required defaults to false', function () {
    $result = $this->service->isRetirementSourceDocumentRequired();

    expect($result)->toBeFalse();
});
test('set require retirement source documents to true', function () {
    $this->service->setRequireRetirementSourceDocuments(true);

    expect($this->service->isRetirementSourceDocumentRequired())->toBeTrue();
});
test('retirement reminder settings return defaults when not configured', function () {
    $settings = $this->service->getRetirementReminderSettings();

    expect($settings['grace_period_days'])->toBe(7);
    expect($settings['frequency_days'])->toBe(7);
    expect($settings['notify_submitter'])->toBeTrue();
    expect($settings['notify_approvers'])->toBeTrue();
    expect($settings['notify_role_ids'])->toBe([]);
});
test('set and get retirement reminder settings', function () {
    $data = [
        'grace_period_days' => 14,
        'frequency_days' => 3,
        'notify_submitter' => false,
        'notify_approvers' => true,
        'notify_role_ids' => [1, 2, 3],
    ];

    $this->service->setRetirementReminderSettings($data);
    $result = $this->service->getRetirementReminderSettings();

    expect($result['grace_period_days'])->toBe(14);
    expect($result['frequency_days'])->toBe(3);
    expect($result['notify_submitter'])->toBeFalse();
    expect($result['notify_approvers'])->toBeTrue();
    expect($result['notify_role_ids'])->toBe([1, 2, 3]);
});
test('get sso default branch id returns null when not set', function () {
    $result = $this->service->getSsoDefaultBranchId();

    expect($result)->toBeNull();
});
test('set and get sso default branch', function () {
    $this->service->setSsoDefaultBranch($this->branch->id);
    $result = $this->service->getSsoDefaultBranchId();

    expect($result)->toBe($this->branch->id);
});
test('set sso default branch to null', function () {
    $this->service->setSsoDefaultBranch($this->branch->id);
    $this->service->setSsoDefaultBranch(null);

    $result = $this->service->getSsoDefaultBranchId();

    expect($result)->toBeNull();
});
