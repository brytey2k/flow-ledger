<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Landlord;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Services\AuditLogRecorder;
use App\Services\TenantUserAdminService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use LogicException;

class TenantUserAdminController extends Controller
{
    public function index(Request $request, Tenant $tenant, TenantUserAdminService $users): View
    {
        $search = trim($request->string('q')->toString());

        return view('landlord.tenants.user-admin', [
            'tenant' => $tenant,
            'users' => $users->list($tenant, $search),
            'search' => $search,
        ]);
    }

    public function forceLogout(Tenant $tenant, int $userId, TenantUserAdminService $users, AuditLogRecorder $audit): RedirectResponse
    {
        return $this->act($tenant, $audit, 'force-logout-user', $userId, fn() => $users->forceLogout($tenant, $userId), __('flash.tenants.user_logged_out'));
    }

    public function resendInvite(Tenant $tenant, int $userId, TenantUserAdminService $users, AuditLogRecorder $audit): RedirectResponse
    {
        return $this->act($tenant, $audit, 'resend-user-invite', $userId, fn() => $users->resendInvite($tenant, $userId), __('flash.tenants.user_invite_resent'));
    }

    public function unlock(Tenant $tenant, int $userId, TenantUserAdminService $users, AuditLogRecorder $audit): RedirectResponse
    {
        return $this->act($tenant, $audit, 'unlock-user', $userId, fn() => $users->unlock($tenant, $userId), __('flash.tenants.user_unlocked'));
    }

    private function act(Tenant $tenant, AuditLogRecorder $audit, string $action, int $userId, \Closure $callback, string $success): RedirectResponse
    {
        try {
            $audit->record($tenant->tenantId(), $action, ['user_id' => $userId], function () use ($callback): array {
                $callback();

                return ['users' => 1];
            });
        } catch (ModelNotFoundException) {
            return back()->with('error', __('flash.tenants.user_not_found'));
        } catch (LogicException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', $success);
    }
}
