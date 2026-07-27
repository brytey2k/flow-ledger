<?php

declare(strict_types=1);

namespace App\Http\Requests\Landlord;

use App\Models\Tenant;
use App\Services\LandlordTenantAccessControlService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UserRolesSyncRequest extends FormRequest
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
            'roles' => ['nullable', 'array'],
            'roles.*' => ['integer'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            /** @var Tenant $tenant */
            $tenant = $this->route('tenant');

            /** @var array<int, int|string> $rawRoleIds */
            $rawRoleIds = $this->input('roles', []);
            $roleIds = array_map('intval', $rawRoleIds);

            $unknownIds = app(LandlordTenantAccessControlService::class)
                ->idsNotBelongingToTenantRoles($tenant, $roleIds);

            if ($unknownIds !== []) {
                $validator->errors()->add('roles', 'One or more selected roles do not belong to this tenant.');
            }
        });
    }
}
