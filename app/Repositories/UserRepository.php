<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Tenant\User;
use Illuminate\Database\Eloquent\Collection;

class UserRepository
{
    /**
     * @param array{q?: string, status?: string, role_id?: int} $filters
     *
     * @return Collection<int, User>
     */
    public function allWithRoles(array $filters = []): Collection
    {
        $search = $filters['q'] ?? null;
        $status = $filters['status'] ?? null;
        $roleId = $filters['role_id'] ?? null;

        return User::with('roles')
            ->when($search, fn($query) => $query->where(
                fn($query) => $query->where('first_name', 'ilike', "%{$search}%")
                    ->orWhere('last_name', 'ilike', "%{$search}%")
                    ->orWhere('email', 'ilike', "%{$search}%"),
            ))
            ->when($status, fn($query) => $query->where('status', $status))
            ->when($roleId, fn($query) => $query->whereHas('roles', fn($query) => $query->whereKey($roleId)))
            ->orderBy('created_at', 'desc')
            ->orderByDesc('id')
            ->get();
    }

    public function findByOidcSub(string $oidcSub): User|null
    {
        return User::query()->where('oidc_sub', $oidcSub)->first();
    }
}
