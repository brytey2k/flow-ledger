<?php

declare(strict_types=1);

namespace App\Http\Requests\Landlord;

use App\Models\Tenant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class TenantUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::guard('landlord')->check();
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        /** @var Tenant $tenant */
        $tenant = $this->route('tenant');

        return [
            'name' => ['required', 'string', 'max:255'],
            'idp_tenant_id' => [
                'nullable',
                'string',
                Rule::unique('tenants', 'idp_tenant_id')->ignore($tenant->getKey(), 'id'),
            ],
        ];
    }
}
