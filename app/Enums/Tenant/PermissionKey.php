<?php

declare(strict_types=1);

namespace App\Enums\Tenant;

enum PermissionKey: string
{
    case AccessLevels = 'access levels';
    case AccessBranches = 'access branches';
    case AccessUsers = 'access users';
    case AccessRoles = 'access roles';
    case AccessCurrencies = 'access currencies';
    case AccessDepartments = 'access departments';
    case AccessCostCodes = 'access cost codes';
    case AccessPositions = 'access positions';
    case AccessStaff = 'access staff';
    case AccessSettings = 'access settings';
    case AccessReports = 'access reports';
    case AccessActivityLog = 'access activity log';

    case ViewAccounts = 'view accounts';
    case CreateAccounts = 'create accounts';
    case EditAccounts = 'edit accounts';
    case DeleteAccounts = 'delete accounts';

    case ViewTransactions = 'view transactions';
    case CreateTransactions = 'create transactions';
    case EditTransactions = 'edit transactions';
    case DeleteTransactions = 'delete transactions';

    case CreateLevel = 'create level';
    case CreateBranch = 'create branch';
    case CreateUser = 'create user';
    case CreateRole = 'create role';
    case CreateCurrency = 'create currency';
    case CreateDepartment = 'create department';
    case CreateCostCode = 'create cost code';
    case CreatePosition = 'create position';
    case CreateStaff = 'create staff';

    case DeleteUser = 'delete user';
    case ManageUserPermissions = 'manage user permissions';
    case DeleteRole = 'delete role';
    case ManageRolePermissions = 'manage role permissions';
    case DeleteCurrency = 'delete currency';
    case DeleteDepartment = 'delete department';
    case DeleteCostCode = 'delete cost code';
    case DeletePosition = 'delete position';
    case DeleteStaff = 'delete staff';

    // Payment Requests
    case AccessPaymentRequests = 'access payment requests';
    case CreatePaymentRequest = 'create payment request';
    case EditPaymentRequest = 'edit payment request';
    case DeletePaymentRequest = 'delete payment request';

    // Retirement Requests
    case AccessRetirementRequests = 'access retirement requests';
    case CreateRetirementRequest = 'create retirement request';
    case EditRetirementRequest = 'edit retirement request';

    // Branch Scoping
    case ViewDescendantBranches = 'view descendant branches';

    // Approvals
    case ApproveRequests = 'approve requests';

    // Disbursement
    case DisburseRequests = 'disburse requests';

    // Settlement
    case SettleRetirements = 'settle retirements';

    // Workflow Templates
    case AccessWorkflowTemplates = 'access workflow templates';
    case CreateWorkflowTemplate = 'create workflow template';
    case EditWorkflowTemplate = 'edit workflow template';
    case DeleteWorkflowTemplate = 'delete workflow template';

    // Cashbook
    case AccessCashbook = 'access cashbook';
    case CreateCashbookEntry = 'create cashbook entry';
    case DeleteCashbookEntry = 'delete cashbook entry';

    // Currency Denominations
    case ManageCurrencyDenominations = 'manage currency denominations';

    // Cash Count
    case AccessCashCount = 'access cash count';
    case CreateCashCount = 'create cash count';
    case DeleteCashCount = 'delete cash count';

    public function category(): PermissionCategory
    {
        return match ($this) {
            self::AccessLevels, self::CreateLevel,
            self::AccessBranches, self::CreateBranch,
            self::ViewDescendantBranches => PermissionCategory::LevelsAndBranches,

            self::AccessUsers, self::CreateUser,
            self::DeleteUser, self::ManageUserPermissions => PermissionCategory::Users,

            self::AccessRoles, self::CreateRole,
            self::DeleteRole, self::ManageRolePermissions => PermissionCategory::Roles,

            self::ViewAccounts, self::CreateAccounts,
            self::EditAccounts, self::DeleteAccounts => PermissionCategory::Accounts,

            self::ViewTransactions, self::CreateTransactions,
            self::EditTransactions, self::DeleteTransactions => PermissionCategory::Transactions,

            self::AccessCurrencies, self::CreateCurrency, self::DeleteCurrency,
            self::ManageCurrencyDenominations => PermissionCategory::Currencies,

            self::AccessDepartments, self::CreateDepartment,
            self::DeleteDepartment => PermissionCategory::Departments,

            self::AccessCostCodes, self::CreateCostCode,
            self::DeleteCostCode => PermissionCategory::CostCodes,

            self::AccessPositions, self::CreatePosition, self::DeletePosition,
            self::AccessStaff, self::CreateStaff, self::DeleteStaff => PermissionCategory::PositionsAndStaff,

            self::AccessPaymentRequests, self::CreatePaymentRequest,
            self::EditPaymentRequest, self::DeletePaymentRequest => PermissionCategory::PaymentRequests,

            self::AccessRetirementRequests, self::CreateRetirementRequest,
            self::EditRetirementRequest => PermissionCategory::RetirementRequests,

            self::AccessWorkflowTemplates, self::CreateWorkflowTemplate,
            self::EditWorkflowTemplate, self::DeleteWorkflowTemplate,
            self::ApproveRequests, self::DisburseRequests,
            self::SettleRetirements => PermissionCategory::Workflow,

            self::AccessCashbook, self::CreateCashbookEntry,
            self::DeleteCashbookEntry => PermissionCategory::Cashbook,

            self::AccessCashCount, self::CreateCashCount,
            self::DeleteCashCount => PermissionCategory::CashCount,

            self::AccessReports => PermissionCategory::Reports,

            self::AccessSettings, self::AccessActivityLog => PermissionCategory::SettingsAndActivity,
        };
    }
}
