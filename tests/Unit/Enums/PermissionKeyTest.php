<?php

declare(strict_types=1);
use App\Enums\Tenant\PermissionKey;

test('access levels value', function () {
    expect(PermissionKey::AccessLevels->value)->toBe('access levels');
});
test('access branches value', function () {
    expect(PermissionKey::AccessBranches->value)->toBe('access branches');
});
test('access users value', function () {
    expect(PermissionKey::AccessUsers->value)->toBe('access users');
});
test('manage user permissions value', function () {
    expect(PermissionKey::ManageUserPermissions->value)->toBe('manage user permissions');
});
test('view descendant branches value', function () {
    expect(PermissionKey::ViewDescendantBranches->value)->toBe('view descendant branches');
});
test('approve requests value', function () {
    expect(PermissionKey::ApproveRequests->value)->toBe('approve requests');
});
test('disburse requests value', function () {
    expect(PermissionKey::DisburseRequests->value)->toBe('disburse requests');
});
test('settle retirements value', function () {
    expect(PermissionKey::SettleRetirements->value)->toBe('settle retirements');
});
test('access cashbook value', function () {
    expect(PermissionKey::AccessCashbook->value)->toBe('access cashbook');
});
test('access cash count value', function () {
    expect(PermissionKey::AccessCashCount->value)->toBe('access cash count');
});
test('can create from string', function () {
    expect(PermissionKey::from('access levels'))->toBe(PermissionKey::AccessLevels);
    expect(PermissionKey::from('create user'))->toBe(PermissionKey::CreateUser);
    expect(PermissionKey::from('delete role'))->toBe(PermissionKey::DeleteRole);
});
test('try from returns null for unknown value', function () {
    expect(PermissionKey::tryFrom('unknown permission'))->toBeNull();
});
test('cases returns expected count', function () {
    $cases = PermissionKey::cases();

    expect(count($cases))->toBeGreaterThan(40);
});
