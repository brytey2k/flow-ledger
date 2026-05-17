<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class UserStoreRequest extends FormRequest
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
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'operational_branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['exists:roles,id'],
        ];
    }

    public function toDto(): \App\DTOs\Tenant\CreateUserDto
    {
        /** @var list<int|string> $rawRoles */
        $rawRoles = (array) ($this->input('roles', []) ?? []);

        $branchId = $this->integer('branch_id');

        return new \App\DTOs\Tenant\CreateUserDto(
            firstName: $this->string('first_name')->toString(),
            lastName: $this->string('last_name')->toString(),
            email: $this->string('email')->toString(),
            password: $this->string('password')->toString(),
            branchId: $branchId,
            operationalBranchId: $this->filled('operational_branch_id') ? $this->integer('operational_branch_id') : $branchId,
            roles: array_map(fn(int|string $v): int => (int) $v, $rawRoles),
        );
    }
}
