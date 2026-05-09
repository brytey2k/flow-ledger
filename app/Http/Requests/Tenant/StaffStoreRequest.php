<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StaffStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:150', 'unique:staff,email'],
            'phone' => ['nullable', 'string', 'max:30'],
            'department_id' => ['required', 'integer', 'exists:departments,id'],
            'position_id' => ['required', 'integer', 'exists:positions,id'],
            'user_id' => ['nullable', 'integer', 'exists:users,id', Rule::unique('staff', 'user_id')],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
        ];
    }

    public function toDto(): \App\DTOs\Tenant\StaffDto
    {
        return new \App\DTOs\Tenant\StaffDto(
            firstName: $this->string('first_name')->toString(),
            lastName: $this->string('last_name')->toString(),
            email: $this->filled('email') ? $this->string('email')->toString() : null,
            phone: $this->filled('phone') ? $this->string('phone')->toString() : null,
            departmentId: $this->integer('department_id'),
            positionId: $this->integer('position_id'),
            userId: $this->filled('user_id') ? $this->integer('user_id') : null,
            branchId: $this->filled('branch_id') ? $this->integer('branch_id') : null,
        );
    }
}
