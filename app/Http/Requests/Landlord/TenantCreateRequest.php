<?php

declare(strict_types=1);

namespace App\Http\Requests\Landlord;

use Illuminate\Foundation\Http\FormRequest;

class TenantCreateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
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
            'admin_password' => ['required', 'string', 'min:8'],
        ];
    }
}
