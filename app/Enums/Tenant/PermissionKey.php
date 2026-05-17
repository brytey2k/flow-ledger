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
    case AccessAccountCodes = 'access account codes';
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
    case CreateAccountCode = 'create account code';
    case CreatePosition = 'create position';
    case CreateStaff = 'create staff';

    case DeleteUser = 'delete user';
    case DeleteRole = 'delete role';
    case DeleteCurrency = 'delete currency';
    case DeleteDepartment = 'delete department';
    case DeleteAccountCode = 'delete account code';
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

    // Attachments
    case DeleteAttachment = 'delete attachment';

    // Workflow Templates
    case AccessWorkflowTemplates = 'access workflow templates';
    case CreateWorkflowTemplate = 'create workflow template';
    case EditWorkflowTemplate = 'edit workflow template';
    case DeleteWorkflowTemplate = 'delete workflow template';

    // Cashbook
    case AccessCashbook = 'access cashbook';
    case CreateCashbookEntry = 'create cashbook entry';
    case DeleteCashbookEntry = 'delete cashbook entry';
}
