<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Tenant\CostCode;
use App\Services\SettingsService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TenantAppTestCase;

class SettingsServiceTest extends TenantAppTestCase
{
    private SettingsService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(SettingsService::class);
    }

    // ── Logo URLs ─────────────────────────────────────────────────────────────

    public function test_get_light_logo_url_returns_null_when_not_set(): void
    {
        $result = $this->service->getLightLogoUrl();

        $this->assertNull($result);
    }

    public function test_get_dark_logo_url_returns_null_when_not_set(): void
    {
        $result = $this->service->getDarkLogoUrl();

        $this->assertNull($result);
    }

    public function test_get_small_logo_url_returns_null_when_not_set(): void
    {
        $result = $this->service->getSmallLogoUrl();

        $this->assertNull($result);
    }

    public function test_store_and_get_light_logo_url_returns_route(): void
    {
        Storage::fake('public');
        $file = UploadedFile::fake()->image('logo.png');

        $this->service->storeLightLogo($file);
        $url = $this->service->getLightLogoUrl();

        $this->assertNotNull($url);
        $this->assertIsString($url);
    }

    public function test_remove_light_logo_makes_url_null(): void
    {
        Storage::fake('public');
        $file = UploadedFile::fake()->image('logo.png');
        $this->service->storeLightLogo($file);

        $this->service->removeLightLogo();
        $url = $this->service->getLightLogoUrl();

        $this->assertNull($url);
    }

    public function test_store_and_get_dark_logo_url_returns_route(): void
    {
        Storage::fake('public');
        $file = UploadedFile::fake()->image('dark_logo.png');

        $this->service->storeDarkLogo($file);
        $url = $this->service->getDarkLogoUrl();

        $this->assertNotNull($url);
        $this->assertIsString($url);
    }

    public function test_remove_dark_logo_makes_url_null(): void
    {
        Storage::fake('public');
        $file = UploadedFile::fake()->image('dark_logo.png');
        $this->service->storeDarkLogo($file);

        $this->service->removeDarkLogo();
        $url = $this->service->getDarkLogoUrl();

        $this->assertNull($url);
    }

    public function test_store_and_get_small_logo_url_returns_route(): void
    {
        Storage::fake('public');
        $file = UploadedFile::fake()->image('small_logo.png');

        $this->service->storeSmallLogo($file);
        $url = $this->service->getSmallLogoUrl();

        $this->assertNotNull($url);
        $this->assertIsString($url);
    }

    public function test_remove_small_logo_makes_url_null(): void
    {
        Storage::fake('public');
        $file = UploadedFile::fake()->image('small_logo.png');
        $this->service->storeSmallLogo($file);

        $this->service->removeSmallLogo();
        $url = $this->service->getSmallLogoUrl();

        $this->assertNull($url);
    }

    // ── DefaultAdvanceCostCode ────────────────────────────────────────────────

    public function test_get_default_advance_cost_code_id_returns_null_when_not_set(): void
    {
        $result = $this->service->getDefaultAdvanceCostCodeId();

        $this->assertNull($result);
    }

    public function test_get_default_advance_cost_code_returns_null_when_not_set(): void
    {
        $result = $this->service->getDefaultAdvanceCostCode();

        $this->assertNull($result);
    }

    public function test_set_and_get_default_advance_cost_code(): void
    {
        $costCode = CostCode::factory()->create();

        $this->service->setDefaultAdvanceCostCode($costCode->id);
        $result = $this->service->getDefaultAdvanceCostCodeId();

        $this->assertSame($costCode->id, $result);
    }

    public function test_get_default_advance_cost_code_returns_model_when_set(): void
    {
        $costCode = CostCode::factory()->create();

        $this->service->setDefaultAdvanceCostCode($costCode->id);
        $result = $this->service->getDefaultAdvanceCostCode();

        $this->assertInstanceOf(CostCode::class, $result);
        $this->assertSame($costCode->id, $result->id);
    }

    public function test_set_default_advance_cost_code_to_null(): void
    {
        $costCode = CostCode::factory()->create();
        $this->service->setDefaultAdvanceCostCode($costCode->id);

        $this->service->setDefaultAdvanceCostCode(null);
        $result = $this->service->getDefaultAdvanceCostCodeId();

        $this->assertNull($result);
    }

    // ── Expense Source Documents ──────────────────────────────────────────────

    public function test_expense_source_document_required_defaults_to_false(): void
    {
        $result = $this->service->isExpenseSourceDocumentRequired();

        $this->assertFalse($result);
    }

    public function test_set_require_expense_source_documents_to_true(): void
    {
        $this->service->setRequireExpenseSourceDocuments(true);

        $this->assertTrue($this->service->isExpenseSourceDocumentRequired());
    }

    public function test_set_require_expense_source_documents_to_false(): void
    {
        $this->service->setRequireExpenseSourceDocuments(true);
        $this->service->setRequireExpenseSourceDocuments(false);

        $this->assertFalse($this->service->isExpenseSourceDocumentRequired());
    }

    // ── Retirement Source Documents ───────────────────────────────────────────

    public function test_retirement_source_document_required_defaults_to_false(): void
    {
        $result = $this->service->isRetirementSourceDocumentRequired();

        $this->assertFalse($result);
    }

    public function test_set_require_retirement_source_documents_to_true(): void
    {
        $this->service->setRequireRetirementSourceDocuments(true);

        $this->assertTrue($this->service->isRetirementSourceDocumentRequired());
    }

    // ── Retirement Reminder Settings ──────────────────────────────────────────

    public function test_retirement_reminder_settings_return_defaults_when_not_configured(): void
    {
        $settings = $this->service->getRetirementReminderSettings();

        $this->assertSame(7, $settings['grace_period_days']);
        $this->assertSame(7, $settings['frequency_days']);
        $this->assertTrue($settings['notify_submitter']);
        $this->assertTrue($settings['notify_approvers']);
        $this->assertSame([], $settings['notify_role_ids']);
    }

    public function test_set_and_get_retirement_reminder_settings(): void
    {
        $data = [
            'grace_period_days' => 14,
            'frequency_days' => 3,
            'notify_submitter' => false,
            'notify_approvers' => true,
            'notify_role_ids' => [1, 2, 3],
        ];

        $this->service->setRetirementReminderSettings($data);
        $result = $this->service->getRetirementReminderSettings();

        $this->assertSame(14, $result['grace_period_days']);
        $this->assertSame(3, $result['frequency_days']);
        $this->assertFalse($result['notify_submitter']);
        $this->assertTrue($result['notify_approvers']);
        $this->assertSame([1, 2, 3], $result['notify_role_ids']);
    }

    // ── SSO Default Branch ────────────────────────────────────────────────────

    public function test_get_sso_default_branch_id_returns_null_when_not_set(): void
    {
        $result = $this->service->getSsoDefaultBranchId();

        $this->assertNull($result);
    }

    public function test_set_and_get_sso_default_branch(): void
    {
        $this->service->setSsoDefaultBranch($this->branch->id);
        $result = $this->service->getSsoDefaultBranchId();

        $this->assertSame($this->branch->id, $result);
    }

    public function test_set_sso_default_branch_to_null(): void
    {
        $this->service->setSsoDefaultBranch($this->branch->id);
        $this->service->setSsoDefaultBranch(null);

        $result = $this->service->getSsoDefaultBranchId();

        $this->assertNull($result);
    }
}
