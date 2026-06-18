<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\Tenant\PermissionKey;
use PHPUnit\Framework\TestCase;

class PermissionKeyTest extends TestCase
{
    public function test_access_levels_value(): void
    {
        $this->assertSame('access levels', PermissionKey::AccessLevels->value);
    }

    public function test_access_branches_value(): void
    {
        $this->assertSame('access branches', PermissionKey::AccessBranches->value);
    }

    public function test_access_users_value(): void
    {
        $this->assertSame('access users', PermissionKey::AccessUsers->value);
    }

    public function test_view_descendant_branches_value(): void
    {
        $this->assertSame('view descendant branches', PermissionKey::ViewDescendantBranches->value);
    }

    public function test_approve_requests_value(): void
    {
        $this->assertSame('approve requests', PermissionKey::ApproveRequests->value);
    }

    public function test_disburse_requests_value(): void
    {
        $this->assertSame('disburse requests', PermissionKey::DisburseRequests->value);
    }

    public function test_settle_retirements_value(): void
    {
        $this->assertSame('settle retirements', PermissionKey::SettleRetirements->value);
    }

    public function test_access_cashbook_value(): void
    {
        $this->assertSame('access cashbook', PermissionKey::AccessCashbook->value);
    }

    public function test_access_cash_count_value(): void
    {
        $this->assertSame('access cash count', PermissionKey::AccessCashCount->value);
    }

    public function test_can_create_from_string(): void
    {
        $this->assertSame(PermissionKey::AccessLevels, PermissionKey::from('access levels'));
        $this->assertSame(PermissionKey::CreateUser, PermissionKey::from('create user'));
        $this->assertSame(PermissionKey::DeleteRole, PermissionKey::from('delete role'));
    }

    public function test_try_from_returns_null_for_unknown_value(): void
    {
        $this->assertNull(PermissionKey::tryFrom('unknown permission'));
    }

    public function test_cases_returns_expected_count(): void
    {
        $cases = PermissionKey::cases();

        $this->assertGreaterThan(40, count($cases));
    }
}
