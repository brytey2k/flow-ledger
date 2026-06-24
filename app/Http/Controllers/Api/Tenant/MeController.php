<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Tenant;

use Illuminate\Http\JsonResponse;

class MeController extends BaseApiController
{
    public function __invoke(): JsonResponse
    {
        $user = $this->apiUser()->load(['branch', 'operationalBranch', 'staffProfile.department', 'roles']);

        /** @var list<string> $permissions */
        $permissions = $user->getAllPermissions()->pluck('name')->values()->all();

        return response()->json([
            'data' => [
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'locale' => $user->locale,
                'branch' => $user->branch ? [
                    'id' => $user->branch->id,
                    'name' => $user->branch->name,
                ] : null,
                'operational_branch' => $user->operationalBranch ? [
                    'id' => $user->operationalBranch->id,
                    'name' => $user->operationalBranch->name,
                ] : null,
                'staff_profile' => $user->staffProfile ? [
                    'id' => $user->staffProfile->id,
                    'department_id' => $user->staffProfile->department_id,
                    'department' => $user->staffProfile->department ? [
                        'id' => $user->staffProfile->department->id,
                        'name' => $user->staffProfile->department->name,
                    ] : null,
                ] : null,
                'roles' => $user->roles->pluck('name')->values()->all(),
                'permissions' => $permissions,
            ],
        ]);
    }
}
