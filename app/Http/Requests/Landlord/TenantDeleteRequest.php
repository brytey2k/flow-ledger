<?php

declare(strict_types=1);

namespace App\Http\Requests\Landlord;

use Illuminate\Foundation\Http\FormRequest;

class TenantDeleteRequest extends FormRequest
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
            'confirm_tenant_name' => ['required', 'string'],
            'delete_database' => ['sometimes', 'boolean'],
        ];
    }
}
