<?php

declare(strict_types=1);

use App\Http\Controllers\Web\Auth\BackchannelLogoutController;
use App\Http\Controllers\Web\Auth\SsoController;
use App\Http\Controllers\Web\Landlord\Auth\LoginController;
use App\Http\Controllers\Web\Landlord\DocumentationController;
use App\Http\Controllers\Web\Landlord\TenantFeatureFlagsController;
use App\Http\Controllers\Web\Landlord\TenantRolesAndPermissionsController;
use App\Http\Controllers\Web\Landlord\TenantsController;
use Illuminate\Support\Facades\Route;

foreach (config('tenancy.central_domains') as $domain) {
    Route::domain($domain)->group(static function () {
        Route::prefix('auth/sso')->name('sso.')->group(static function () {
            Route::get('redirect', [SsoController::class, 'redirect'])->name('redirect');
            Route::get('callback', [SsoController::class, 'callback'])->name('callback');
            Route::post('logout', [SsoController::class, 'logout'])->name('logout');
            Route::post('backchannel-logout', BackchannelLogoutController::class)->name('backchannel-logout');
        });
    });

    Route::domain($domain)->name('landlord.')->group(static function () {
        Route::get('landlord/sys-admin/login', [LoginController::class, 'create'])->name('login');
        Route::post('landlord/sys-admin/login', [LoginController::class, 'store'])->name('do-login')->middleware('throttle:5,1');

        Route::middleware(['auth:landlord'])->group(static function () {
            Route::post('landlord/sys-admin/logout', [LoginController::class, 'logout'])->name('logout');

            Route::get('landlord/sys-admin/tenants', [TenantsController::class, 'index'])->name('tenants.index');
            Route::get('landlord/sys-admin/tenants/create', [TenantsController::class, 'create'])->name('tenants.create');
            Route::post('landlord/sys-admin/tenants', [TenantsController::class, 'store'])->name('tenants.store');
            Route::get('landlord/sys-admin/tenants/{tenant}/edit', [TenantsController::class, 'edit'])->name('tenants.edit');
            Route::put('landlord/sys-admin/tenants/{tenant}', [TenantsController::class, 'update'])->name('tenants.update');
            Route::post('landlord/sys-admin/tenants/{tenant}/suspend', [TenantsController::class, 'suspend'])->name('tenants.suspend');
            Route::post('landlord/sys-admin/tenants/{tenant}/unsuspend', [TenantsController::class, 'unsuspend'])->name('tenants.unsuspend');
            Route::post('landlord/sys-admin/tenants/{tenant}/reset', [TenantsController::class, 'reset'])->name('tenants.reset');
            Route::delete('landlord/sys-admin/tenants/{tenant}', [TenantsController::class, 'destroy'])->name('tenants.destroy');

            Route::get('landlord/sys-admin/documentation', [DocumentationController::class, 'index'])->name('documentation');

            Route::get('landlord/sys-admin/tenants/{tenant}/select-user', [TenantsController::class, 'selectUser'])->name('tenants.select-user');
            Route::get('landlord/sys-admin/tenants/{tenant}/users', [TenantsController::class, 'users'])->name('tenants.users');
            Route::post('landlord/sys-admin/impersonate/{tenant}', [TenantsController::class, 'impersonate'])->name('impersonate');

            Route::get('landlord/sys-admin/feature-flags', [TenantFeatureFlagsController::class, 'overview'])->name('feature-flags.index');
            Route::get('landlord/sys-admin/tenants/{tenant}/feature-flags', [TenantFeatureFlagsController::class, 'index'])->name('tenants.feature-flags.index');
            Route::put('landlord/sys-admin/tenants/{tenant}/feature-flags', [TenantFeatureFlagsController::class, 'update'])->name('tenants.feature-flags.update');
            Route::post('landlord/sys-admin/feature-flags/bulk', [TenantFeatureFlagsController::class, 'bulkUpdate'])->name('feature-flags.bulk-update');

            // Roles & Permissions
            Route::get('landlord/sys-admin/tenants/{tenant}/roles', [TenantRolesAndPermissionsController::class, 'roles'])
                ->name('tenants.roles.index');
            Route::get('landlord/sys-admin/tenants/{tenant}/roles/{role}/permissions', [TenantRolesAndPermissionsController::class, 'editRolePermissions'])
                ->name('tenants.roles.permissions.edit');
            Route::put('landlord/sys-admin/tenants/{tenant}/roles/{role}/permissions', [TenantRolesAndPermissionsController::class, 'updateRolePermissions'])
                ->name('tenants.roles.permissions.update');

            Route::get('landlord/sys-admin/tenants/{tenant}/users-permissions', [TenantRolesAndPermissionsController::class, 'users'])
                ->name('tenants.users-permissions.index');
            Route::get('landlord/sys-admin/tenants/{tenant}/users/{user}/roles', [TenantRolesAndPermissionsController::class, 'editUserRoles'])
                ->name('tenants.users.roles.edit');
            Route::put('landlord/sys-admin/tenants/{tenant}/users/{user}/roles', [TenantRolesAndPermissionsController::class, 'updateUserRoles'])
                ->name('tenants.users.roles.update');
            Route::get('landlord/sys-admin/tenants/{tenant}/users/{user}/permissions', [TenantRolesAndPermissionsController::class, 'editUserPermissions'])
                ->name('tenants.users.permissions.edit');
            Route::put('landlord/sys-admin/tenants/{tenant}/users/{user}/permissions', [TenantRolesAndPermissionsController::class, 'updateUserPermissions'])
                ->name('tenants.users.permissions.update');
        });
    });
}
