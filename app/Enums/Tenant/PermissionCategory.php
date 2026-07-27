<?php

declare(strict_types=1);

namespace App\Enums\Tenant;

enum PermissionCategory: string
{
    case LevelsAndBranches = 'levels and branches';
    case Users = 'users';
    case Roles = 'roles';
    case Accounts = 'accounts';
    case Transactions = 'transactions';
    case Currencies = 'currencies';
    case Departments = 'departments';
    case CostCodes = 'cost codes';
    case PositionsAndStaff = 'positions and staff';
    case PaymentRequests = 'payment requests';
    case RetirementRequests = 'retirement requests';
    case Workflow = 'workflow';
    case Cashbook = 'cashbook';
    case CashCount = 'cash count';
    case Reports = 'reports';
    case SettingsAndActivity = 'settings and activity';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::LevelsAndBranches => __('permissions.category_levels_and_branches'),
            self::Users => __('permissions.category_users'),
            self::Roles => __('permissions.category_roles'),
            self::Accounts => __('permissions.category_accounts'),
            self::Transactions => __('permissions.category_transactions'),
            self::Currencies => __('permissions.category_currencies'),
            self::Departments => __('permissions.category_departments'),
            self::CostCodes => __('permissions.category_cost_codes'),
            self::PositionsAndStaff => __('permissions.category_positions_and_staff'),
            self::PaymentRequests => __('permissions.category_payment_requests'),
            self::RetirementRequests => __('permissions.category_retirement_requests'),
            self::Workflow => __('permissions.category_workflow'),
            self::Cashbook => __('permissions.category_cashbook'),
            self::CashCount => __('permissions.category_cash_count'),
            self::Reports => __('permissions.category_reports'),
            self::SettingsAndActivity => __('permissions.category_settings_and_activity'),
            self::Other => __('permissions.category_other'),
        };
    }
}
