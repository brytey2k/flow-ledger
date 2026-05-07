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
}
