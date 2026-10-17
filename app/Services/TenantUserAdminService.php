<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\Tenant\UserStatus;
use App\Interfaces\SessionInvalidatorInterface;
use App\Jobs\InviteUserToIamJob;
use App\Models\Tenant;
use App\Models\Tenant\User;
use App\Notifications\WelcomeNotification;
use Illuminate\Support\Facades\Password;
use LogicException;

class TenantUserAdminService
{
    public function __construct(private readonly SessionInvalidatorInterface $sessions) {}

    /** @return list<array{id: int, name: string, email: string, status: string, sso_linked: bool, is_oidc_user: bool}> */
    public function list(Tenant $tenant, string $search = '', int $limit = 100): array
    {
        /** @var list<array{id: int, name: string, email: string, status: string, sso_linked: bool, is_oidc_user: bool}> $users */
        $users = $tenant->run(fn(): array => User::query()
            ->when($search !== '', fn($query) => $query->where(fn($query) => $query
                ->where('email', 'ilike', "%{$search}%")
                ->orWhere('first_name', 'ilike', "%{$search}%")
                ->orWhere('last_name', 'ilike', "%{$search}%")))
            ->orderBy('first_name')
            ->limit($limit)
            ->get()
            ->map(fn(User $user): array => [
                'id' => $user->id,
                'name' => trim($user->first_name . ' ' . $user->last_name),
                'email' => $user->email,
                'status' => $user->status->value,
                'sso_linked' => $user->oidc_sub !== null,
                'is_oidc_user' => (bool) $user->is_oidc_user,
            ])
            ->all());

        return $users;
    }

    public function forceLogout(Tenant $tenant, int $userId): void
    {
        $tenant->run(function () use ($userId): void {
            $this->sessions->invalidate(User::findOrFail($userId)->id);
        });
    }

    public function resendInvite(Tenant $tenant, int $userId): void
    {
        $tenant->run(function () use ($tenant, $userId): void {
            $user = User::findOrFail($userId);

            if ($user->status !== UserStatus::Invited) {
                throw new LogicException('Only users who have not yet accepted can be re-invited.');
            }

            if ($user->is_oidc_user) {
                $idpTenantId = $tenant->getAttribute('idp_tenant_id');
                InviteUserToIamJob::dispatch($user->id, is_scalar($idpTenantId) ? (string) $idpTenantId : null);

                return;
            }

            $user->notify(new WelcomeNotification(Password::broker()->createToken($user)));
        });
    }

    /** Lifts a suspension so the user can sign in again. */
    public function unlock(Tenant $tenant, int $userId): void
    {
        $tenant->run(function () use ($userId): void {
            $user = User::findOrFail($userId);

            if ($user->status !== UserStatus::Suspended) {
                throw new LogicException('Only suspended users can be unlocked.');
            }

            $user->update(['status' => UserStatus::Active]);
        });
    }
}
