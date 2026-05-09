<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;

class RoleStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:roles,name'],
            'guard_name' => ['nullable', 'string', 'max:255'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'guard_name' => $this->guard_name ?? 'web',
        ]);
    }

    public function toDto(): \App\DTOs\Tenant\CreateRoleDto
    {
        return new \App\DTOs\Tenant\CreateRoleDto(
            name: $this->string('name')->toString(),
            guardName: $this->filled('guard_name') ? $this->string('guard_name')->toString() : 'web',
        );
    }
}
