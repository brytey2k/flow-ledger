<?php

declare(strict_types=1);

namespace App\Http\Requests\Landlord;

use App\Models\Tenant;
use App\Services\LandlordTenantAccessControlService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class RolePermissionsSyncRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('landlord') !== null;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['integer'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            /** @var Tenant $tenant */
            $tenant = $this->route('tenant');

            /** @var array<int, int|string> $rawPermissionIds */
            $rawPermissionIds = $this->input('permissions', []);
            $permissionIds = array_map('intval', $rawPermissionIds);

            $unknownIds = app(LandlordTenantAccessControlService::class)
                ->idsNotBelongingToTenantPermissions($tenant, $permissionIds);

            if ($unknownIds !== []) {
                $validator->errors()->add('permissions', 'One or more selected permissions do not belong to this tenant.');
            }
        });
    }
}
