<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\Tenant\SettingKey;
use PHPUnit\Framework\TestCase;

class SettingKeyTest extends TestCase
{
    public function test_cases_returns_nine_keys(): void
    {
        $this->assertCount(9, SettingKey::cases());
    }

    public function test_logo_light_value(): void
    {
        $this->assertSame('logo', SettingKey::LogoLight->value);
    }

    public function test_logo_dark_value(): void
    {
        $this->assertSame('logo_dark', SettingKey::LogoDark->value);
    }

    public function test_logo_small_value(): void
    {
        $this->assertSame('logo_small', SettingKey::LogoSmall->value);
    }

    public function test_default_advance_cost_code_value(): void
    {
        $this->assertSame('default_advance_cost_code', SettingKey::DefaultAdvanceCostCode->value);
    }

    public function test_require_expense_source_documents_value(): void
    {
        $this->assertSame('require_expense_source_documents', SettingKey::RequireExpenseSourceDocuments->value);
    }

    public function test_require_retirement_source_documents_value(): void
    {
        $this->assertSame('require_retirement_source_documents', SettingKey::RequireRetirementSourceDocuments->value);
    }

    public function test_retirement_reminders_value(): void
    {
        $this->assertSame('retirement_reminders', SettingKey::RetirementReminders->value);
    }

    public function test_sso_default_branch_value(): void
    {
        $this->assertSame('sso_default_branch', SettingKey::SsoDefaultBranch->value);
    }

    public function test_sso_staff_role_value(): void
    {
        $this->assertSame('sso_staff_role', SettingKey::SsoStaffRole->value);
    }

    public function test_can_create_from_string(): void
    {
        $this->assertSame(SettingKey::LogoLight, SettingKey::from('logo'));
        $this->assertSame(SettingKey::RetirementReminders, SettingKey::from('retirement_reminders'));
    }

    public function test_try_from_returns_null_for_unknown_value(): void
    {
        $this->assertNull(SettingKey::tryFrom('unknown_key'));
    }
}
