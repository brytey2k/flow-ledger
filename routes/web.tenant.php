<?php

declare(strict_types=1);

use App\Enums\Tenant\PermissionKey;
use App\Http\Controllers\Web\Tenant\AccountCodesController;
use App\Http\Controllers\Web\Tenant\Auth\LoginController;
use App\Http\Controllers\Web\Tenant\BranchesController;
use App\Http\Controllers\Web\Tenant\CurrenciesController;
use App\Http\Controllers\Web\Tenant\DashboardController;
use App\Http\Controllers\Web\Tenant\DepartmentsController;
use App\Http\Controllers\Web\Tenant\DocumentationController;
use App\Http\Controllers\Web\Tenant\LevelController;
use App\Http\Controllers\Web\Tenant\PositionsController;
use App\Http\Controllers\Web\Tenant\RolesController;
use App\Http\Controllers\Web\Tenant\StaffController;
use App\Http\Controllers\Web\Tenant\UsersController;
use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

Route::middleware([
    'web',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
    'tenant.active',
])->group(function (): void {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('do-login');

    Route::middleware(['auth'])->group(function (): void {
        Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/documentation', [DocumentationController::class, 'index'])->name('documentation');

        // Levels
        Route::get('/levels', [LevelController::class, 'index'])
            ->can(PermissionKey::AccessLevels->value)
            ->name('levels.index');
        Route::get('/levels/create', [LevelController::class, 'create'])
            ->can(PermissionKey::CreateLevel->value)
            ->name('levels.create');
        Route::post('/levels', [LevelController::class, 'store'])
            ->can(PermissionKey::CreateLevel->value)
            ->name('levels.store');
        Route::get('/levels/{level}/edit', [LevelController::class, 'edit'])
            ->can(PermissionKey::AccessLevels->value)
            ->name('levels.edit');
        Route::put('/levels/{level}', [LevelController::class, 'update'])
            ->can(PermissionKey::AccessLevels->value)
            ->name('levels.update');
        Route::delete('/levels/{level}', [LevelController::class, 'destroy'])
            ->can(PermissionKey::AccessLevels->value)
            ->name('levels.destroy');

        // Departments
        Route::get('/departments', [DepartmentsController::class, 'index'])
            ->can(PermissionKey::AccessDepartments->value)
            ->name('departments.index');
        Route::get('/departments/create', [DepartmentsController::class, 'create'])
            ->can(PermissionKey::CreateDepartment->value)
            ->name('departments.create');
        Route::post('/departments', [DepartmentsController::class, 'store'])
            ->can(PermissionKey::CreateDepartment->value)
            ->name('departments.store');
        Route::get('/departments/{department}/edit', [DepartmentsController::class, 'edit'])
            ->can(PermissionKey::AccessDepartments->value)
            ->name('departments.edit');
        Route::put('/departments/{department}', [DepartmentsController::class, 'update'])
            ->can(PermissionKey::AccessDepartments->value)
            ->name('departments.update');
        Route::delete('/departments/{department}', [DepartmentsController::class, 'destroy'])
            ->can(PermissionKey::DeleteDepartment->value)
            ->name('departments.destroy');

        // Account Codes
        Route::get('/account-codes', [AccountCodesController::class, 'index'])
            ->can(PermissionKey::AccessAccountCodes->value)
            ->name('account-codes.index');
        Route::get('/account-codes/create', [AccountCodesController::class, 'create'])
            ->can(PermissionKey::CreateAccountCode->value)
            ->name('account-codes.create');
        Route::post('/account-codes', [AccountCodesController::class, 'store'])
            ->can(PermissionKey::CreateAccountCode->value)
            ->name('account-codes.store');
        Route::get('/account-codes/{account_code}/edit', [AccountCodesController::class, 'edit'])
            ->can(PermissionKey::AccessAccountCodes->value)
            ->name('account-codes.edit');
        Route::put('/account-codes/{account_code}', [AccountCodesController::class, 'update'])
            ->can(PermissionKey::AccessAccountCodes->value)
            ->name('account-codes.update');
        Route::delete('/account-codes/{account_code}', [AccountCodesController::class, 'destroy'])
            ->can(PermissionKey::DeleteAccountCode->value)
            ->name('account-codes.destroy');

        // Positions
        Route::get('/positions', [PositionsController::class, 'index'])
            ->can(PermissionKey::AccessPositions->value)
            ->name('positions.index');
        Route::get('/positions/create', [PositionsController::class, 'create'])
            ->can(PermissionKey::CreatePosition->value)
            ->name('positions.create');
        Route::post('/positions', [PositionsController::class, 'store'])
            ->can(PermissionKey::CreatePosition->value)
            ->name('positions.store');
        Route::get('/positions/{position}/edit', [PositionsController::class, 'edit'])
            ->can(PermissionKey::AccessPositions->value)
            ->name('positions.edit');
        Route::put('/positions/{position}', [PositionsController::class, 'update'])
            ->can(PermissionKey::AccessPositions->value)
            ->name('positions.update');
        Route::delete('/positions/{position}', [PositionsController::class, 'destroy'])
            ->can(PermissionKey::DeletePosition->value)
            ->name('positions.destroy');

        // Staff
        Route::get('/staff', [StaffController::class, 'index'])
            ->can(PermissionKey::AccessStaff->value)
            ->name('staff.index');
        Route::get('/staff/create', [StaffController::class, 'create'])
            ->can(PermissionKey::CreateStaff->value)
            ->name('staff.create');
        Route::post('/staff', [StaffController::class, 'store'])
            ->can(PermissionKey::CreateStaff->value)
            ->name('staff.store');
        Route::get('/staff/{staff}/edit', [StaffController::class, 'edit'])
            ->can(PermissionKey::AccessStaff->value)
            ->name('staff.edit');
        Route::put('/staff/{staff}', [StaffController::class, 'update'])
            ->can(PermissionKey::AccessStaff->value)
            ->name('staff.update');
        Route::delete('/staff/{staff}', [StaffController::class, 'destroy'])
            ->can(PermissionKey::DeleteStaff->value)
            ->name('staff.destroy');

        // Branches
        Route::get('/branches', [BranchesController::class, 'index'])
            ->can(PermissionKey::AccessBranches->value)
            ->name('branches.index');
        Route::get('/branches/create', [BranchesController::class, 'create'])
            ->can(PermissionKey::CreateBranch->value)
            ->name('branches.create');
        Route::post('/branches', [BranchesController::class, 'store'])
            ->can(PermissionKey::CreateBranch->value)
            ->name('branches.store');
        Route::get('/branches/{branch}/edit', [BranchesController::class, 'edit'])
            ->can(PermissionKey::AccessBranches->value)
            ->name('branches.edit');
        Route::put('/branches/{branch}', [BranchesController::class, 'update'])
            ->can(PermissionKey::AccessBranches->value)
            ->name('branches.update');
        Route::delete('/branches/{branch}', [BranchesController::class, 'destroy'])
            ->can(PermissionKey::AccessBranches->value)
            ->name('branches.destroy');

        // Users
        Route::get('/users', [UsersController::class, 'index'])
            ->can(PermissionKey::AccessUsers->value)
            ->name('users.index');
        Route::get('/users/create', [UsersController::class, 'create'])
            ->can(PermissionKey::CreateUser->value)
            ->name('users.create');
        Route::post('/users', [UsersController::class, 'store'])
            ->can(PermissionKey::CreateUser->value)
            ->name('users.store');
        Route::get('/users/{user}/edit', [UsersController::class, 'edit'])
            ->can(PermissionKey::AccessUsers->value)
            ->name('users.edit');
        Route::put('/users/{user}', [UsersController::class, 'update'])
            ->can(PermissionKey::AccessUsers->value)
            ->name('users.update');
        Route::delete('/users/{user}', [UsersController::class, 'destroy'])
            ->can(PermissionKey::DeleteUser->value)
            ->name('users.destroy');
        Route::get('/users/{user}/permissions', [UsersController::class, 'editPermissions'])
            ->can(PermissionKey::AccessUsers->value)
            ->name('users.permissions.edit');
        Route::put('/users/{user}/permissions', [UsersController::class, 'updatePermissions'])
            ->can(PermissionKey::AccessUsers->value)
            ->name('users.permissions.update');

        // Roles
        Route::get('/roles', [RolesController::class, 'index'])
            ->can(PermissionKey::AccessRoles->value)
            ->name('roles.index');
        Route::get('/roles/create', [RolesController::class, 'create'])
            ->can(PermissionKey::CreateRole->value)
            ->name('roles.create');
        Route::post('/roles', [RolesController::class, 'store'])
            ->can(PermissionKey::CreateRole->value)
            ->name('roles.store');
        Route::get('/roles/{role}/edit', [RolesController::class, 'edit'])
            ->can(PermissionKey::AccessRoles->value)
            ->name('roles.edit');
        Route::put('/roles/{role}', [RolesController::class, 'update'])
            ->can(PermissionKey::AccessRoles->value)
            ->name('roles.update');
        Route::delete('/roles/{role}', [RolesController::class, 'destroy'])
            ->can(PermissionKey::DeleteRole->value)
            ->name('roles.destroy');
        Route::get('/roles/{role}/permissions', [RolesController::class, 'editPermissions'])
            ->can(PermissionKey::AccessRoles->value)
            ->name('roles.permissions.edit');
        Route::put('/roles/{role}/permissions', [RolesController::class, 'updatePermissions'])
            ->can(PermissionKey::AccessRoles->value)
            ->name('roles.permissions.update');

        // Currencies
        Route::get('/currencies', [CurrenciesController::class, 'index'])
            ->can(PermissionKey::AccessCurrencies->value)
            ->name('currencies.index');
        Route::get('/currencies/create', [CurrenciesController::class, 'create'])
            ->can(PermissionKey::CreateCurrency->value)
            ->name('currencies.create');
        Route::post('/currencies', [CurrenciesController::class, 'store'])
            ->can(PermissionKey::CreateCurrency->value)
            ->name('currencies.store');
        Route::get('/currencies/{currency}/edit', [CurrenciesController::class, 'edit'])
            ->can(PermissionKey::AccessCurrencies->value)
            ->name('currencies.edit');
        Route::put('/currencies/{currency}', [CurrenciesController::class, 'update'])
            ->can(PermissionKey::AccessCurrencies->value)
            ->name('currencies.update');
        Route::delete('/currencies/{currency}', [CurrenciesController::class, 'destroy'])
            ->can(PermissionKey::DeleteCurrency->value)
            ->name('currencies.destroy');
    });
});
