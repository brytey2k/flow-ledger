<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RoleUpdateRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255', Rule::unique('roles', 'name')->ignore($this->role)],
        ];
    }

    public function toDto(): \App\DTOs\Tenant\UpdateRoleDto
    {
        return new \App\DTOs\Tenant\UpdateRoleDto(
            name: $this->string('name')->toString(),
        );
    }
}
