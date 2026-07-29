<?php

declare(strict_types=1);
use App\Enums\Tenant\SettingKey;

test('cases returns nine keys', function () {
    expect(SettingKey::cases())->toHaveCount(9);
});
test('logo light value', function () {
    expect(SettingKey::LogoLight->value)->toBe('logo');
});
test('logo dark value', function () {
    expect(SettingKey::LogoDark->value)->toBe('logo_dark');
});
test('logo small value', function () {
    expect(SettingKey::LogoSmall->value)->toBe('logo_small');
});
test('default advance cost code value', function () {
    expect(SettingKey::DefaultAdvanceCostCode->value)->toBe('default_advance_cost_code');
});
test('require expense source documents value', function () {
    expect(SettingKey::RequireExpenseSourceDocuments->value)->toBe('require_expense_source_documents');
});
test('require retirement source documents value', function () {
    expect(SettingKey::RequireRetirementSourceDocuments->value)->toBe('require_retirement_source_documents');
});
test('retirement reminders value', function () {
    expect(SettingKey::RetirementReminders->value)->toBe('retirement_reminders');
});
test('sso default branch value', function () {
    expect(SettingKey::SsoDefaultBranch->value)->toBe('sso_default_branch');
});
test('sso staff role value', function () {
    expect(SettingKey::SsoStaffRole->value)->toBe('sso_staff_role');
});
test('can create from string', function () {
    expect(SettingKey::from('logo'))->toBe(SettingKey::LogoLight);
    expect(SettingKey::from('retirement_reminders'))->toBe(SettingKey::RetirementReminders);
});
test('try from returns null for unknown value', function () {
    expect(SettingKey::tryFrom('unknown_key'))->toBeNull();
});
