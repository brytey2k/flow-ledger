<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\Tenant\PermissionKey;
use App\Models\Tenant\BranchClosure;
use App\Models\Tenant\User;

class BranchScopeService
{
    /**
     * Returns the branch IDs the user is allowed to see data for.
     * Without the ViewDescendantBranches permission, only the user's operational branch is returned.
     * With the permission, the operational branch and all descendant branch IDs are returned.
     *
     * @param User $user
     *
     * @return list<int>
     */
    public function allowedBranchIds(User $user): array
    {
        $branchId = $user->operational_branch_id;

        if ($user->can(PermissionKey::ViewDescendantBranches->value)) {
            /** @var list<int|string> $raw */
            $raw = BranchClosure::where('ancestor_id', $branchId)->pluck('descendant_id')->toArray();

            return array_map(fn(int|string $id): int => (int) $id, $raw);
        }

        return [$branchId];
    }
}
