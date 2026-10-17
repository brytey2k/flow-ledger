<?php

declare(strict_types=1);

namespace App\Http\Requests\Landlord;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;

class TenantCreateRequest extends FormRequest
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
        return [
            'id' => ['required', 'string', 'alpha_dash', 'max:50', 'unique:tenants,id'],
            'name' => ['required', 'string', 'max:255'],
            'admin_email' => ['required', 'email', 'max:255'],
            'admin_password' => ['required', 'string', Password::defaults()],
            'idp_tenant_id' => ['nullable', 'string', 'unique:tenants,idp_tenant_id'],
        ];
    }
}
