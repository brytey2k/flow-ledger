<?php

declare(strict_types=1);

use App\Http\Controllers\Web\Auth\BackchannelLogoutController;
use App\Http\Controllers\Web\Auth\SsoController;
use App\Http\Controllers\Web\Landlord\Auth\LoginController;
use App\Http\Controllers\Web\Landlord\DocumentationController;
use App\Http\Controllers\Web\Landlord\HomeController;
use App\Http\Controllers\Web\Landlord\LocaleController;
use App\Http\Controllers\Web\Landlord\TenantAnnouncementsController;
use App\Http\Controllers\Web\Landlord\TenantDataToolsController;
use App\Http\Controllers\Web\Landlord\TenantFailedJobsController;
use App\Http\Controllers\Web\Landlord\TenantFeatureFlagsController;
use App\Http\Controllers\Web\Landlord\TenantFixUpsController;
use App\Http\Controllers\Web\Landlord\TenantHealthController;
use App\Http\Controllers\Web\Landlord\TenantRolesAndPermissionsController;
use App\Http\Controllers\Web\Landlord\TenantsController;
use App\Http\Controllers\Web\Landlord\TenantStorageController;
use App\Http\Controllers\Web\Landlord\TenantUserAdminController;
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
        Route::get('/', [HomeController::class, 'index'])->name('home');

        // Self-service sign-up is paused while new organizations are onboarded by hand.
        Route::view('register', 'landlord.auth.register-halted')->name('register');

        Route::post('locale', [LocaleController::class, 'update'])->name('locale.update');

        Route::get('landlord/sys-admin/login', [LoginController::class, 'create'])->name('login');
        Route::post('landlord/sys-admin/login', [LoginController::class, 'store'])->name('do-login')->middleware('throttle:5,1');

        Route::middleware(['auth:landlord'])->group(static function () {
            Route::post('landlord/sys-admin/logout', [LoginController::class, 'logout'])->name('logout');
            Route::get('landlord/sys-admin/session/keep-alive', static fn() => response()->noContent())->name('session.keep-alive');

            Route::get('landlord/sys-admin/tenants', [TenantsController::class, 'index'])->name('tenants.index');
            Route::get('landlord/sys-admin/tenants/create', [TenantsController::class, 'create'])->name('tenants.create');
            Route::post('landlord/sys-admin/tenants', [TenantsController::class, 'store'])->name('tenants.store');
            Route::get('landlord/sys-admin/tenants/{tenant}/edit', [TenantsController::class, 'edit'])->name('tenants.edit');
            Route::put('landlord/sys-admin/tenants/{tenant}', [TenantsController::class, 'update'])->name('tenants.update');
            Route::post('landlord/sys-admin/tenants/{tenant}/suspend', [TenantsController::class, 'suspend'])->name('tenants.suspend');
            Route::post('landlord/sys-admin/tenants/{tenant}/unsuspend', [TenantsController::class, 'unsuspend'])->name('tenants.unsuspend');
            Route::post('landlord/sys-admin/tenants/{tenant}/reset', [TenantsController::class, 'reset'])->name('tenants.reset');
            Route::delete('landlord/sys-admin/tenants/{tenant}', [TenantsController::class, 'destroy'])->name('tenants.destroy');

            Route::get('landlord/sys-admin/tenants/{tenant}/data-tools', [TenantDataToolsController::class, 'index'])->name('tenants.data-tools.index');
            Route::post('landlord/sys-admin/tenants/{tenant}/data-tools/{tool}/preview', [TenantDataToolsController::class, 'preview'])->name('tenants.data-tools.preview');
            Route::post('landlord/sys-admin/tenants/{tenant}/data-tools/{tool}', [TenantDataToolsController::class, 'run'])->name('tenants.data-tools.run')->middleware('throttle:6,1');

            Route::get('landlord/sys-admin/tenants/{tenant}/data-tools/runs/{auditLog}/download', [TenantDataToolsController::class, 'download'])->name('tenants.data-tools.download');

            Route::get('landlord/sys-admin/tenants/{tenant}/fix-ups', [TenantFixUpsController::class, 'index'])->name('tenants.fix-ups.index');
            Route::post('landlord/sys-admin/tenants/{tenant}/fix-ups/{fixUp}', [TenantFixUpsController::class, 'run'])->name('tenants.fix-ups.run')->middleware('throttle:10,1');

            Route::get('landlord/sys-admin/tenants/{tenant}/health', [TenantHealthController::class, 'index'])->name('tenants.health.index');

            Route::get('landlord/sys-admin/tenants/{tenant}/failed-jobs', [TenantFailedJobsController::class, 'index'])->name('tenants.failed-jobs.index');
            Route::post('landlord/sys-admin/tenants/{tenant}/failed-jobs/{scope}/{uuid}/retry', [TenantFailedJobsController::class, 'retry'])->name('tenants.failed-jobs.retry');
            Route::delete('landlord/sys-admin/tenants/{tenant}/failed-jobs/{scope}/{uuid}', [TenantFailedJobsController::class, 'discard'])->name('tenants.failed-jobs.discard');

            Route::get('landlord/sys-admin/tenants/{tenant}/storage', [TenantStorageController::class, 'index'])->name('tenants.storage.index');
            Route::post('landlord/sys-admin/tenants/{tenant}/storage/cleanup', [TenantStorageController::class, 'cleanup'])->name('tenants.storage.cleanup')->middleware('throttle:6,1');

            Route::get('landlord/sys-admin/tenants/{tenant}/user-admin', [TenantUserAdminController::class, 'index'])->name('tenants.user-admin.index');
            Route::post('landlord/sys-admin/tenants/{tenant}/user-admin/{userId}/force-logout', [TenantUserAdminController::class, 'forceLogout'])->name('tenants.user-admin.force-logout');
            Route::post('landlord/sys-admin/tenants/{tenant}/user-admin/{userId}/resend-invite', [TenantUserAdminController::class, 'resendInvite'])->name('tenants.user-admin.resend-invite');
            Route::post('landlord/sys-admin/tenants/{tenant}/user-admin/{userId}/unlock', [TenantUserAdminController::class, 'unlock'])->name('tenants.user-admin.unlock');

            Route::get('landlord/sys-admin/tenants/{tenant}/announcements', [TenantAnnouncementsController::class, 'edit'])->name('tenants.announcements.edit');
            Route::put('landlord/sys-admin/tenants/{tenant}/announcements', [TenantAnnouncementsController::class, 'update'])->name('tenants.announcements.update');

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
