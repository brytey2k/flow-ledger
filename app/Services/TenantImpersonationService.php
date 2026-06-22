<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Tenant\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Stancl\Tenancy\Contracts\Tenant as TenantContract;
use Stancl\Tenancy\Database\Models\ImpersonationToken;

class TenantImpersonationService
{
    public function createImpersonationToken(TenantContract $tenant, User $user, string $redirectUrl = '/dashboard', string $guard = 'web'): ImpersonationToken
    {
        /** @var ImpersonationToken $result */
        $result = $tenant->run(function () use ($tenant, $user, $redirectUrl, $guard): ImpersonationToken {
            // @phpstan-ignore-next-line
            return tenancy()->impersonate($tenant, (string) $user->id, $redirectUrl, $guard);
        });

        return $result;
    }

    public function findTenantUser(TenantContract $tenant, string $identifier): User|null
    {
        /** @var User|null $result */
        $result = $tenant->run(fn(): User|null => User::withTrashed(false)
            ->where('id', $identifier)
            ->orWhere('email', $identifier)
            ->first());

        return $result;
    }

    /** @return LengthAwarePaginator<int, User> */
    public function getTenantUsersPaginated(TenantContract $tenant, int $perPage = 15, int $page = 1): LengthAwarePaginator
    {
        /** @var LengthAwarePaginator<int, User> $result */
        $result = $tenant->run(fn() => User::with('branch')->orderBy('first_name')->paginate($perPage, ['*'], 'page', $page));

        return $result;
    }

    /** @return \Illuminate\Database\Eloquent\Collection<int, User> */
    public function searchTenantUsers(TenantContract $tenant, string $query, int $limit = 100): \Illuminate\Database\Eloquent\Collection
    {
        /** @var \Illuminate\Database\Eloquent\Collection<int, User> $result */
        $result = $tenant->run(function () use ($query, $limit): \Illuminate\Database\Eloquent\Collection {
            return User::where(function ($q) use ($query): void {
                $q->where('first_name', 'like', "%{$query}%")
                    ->orWhere('last_name', 'like', "%{$query}%")
                    ->orWhere('email', 'like', "%{$query}%");
            })->limit($limit)->get();
        });

        return $result;
    }
}
