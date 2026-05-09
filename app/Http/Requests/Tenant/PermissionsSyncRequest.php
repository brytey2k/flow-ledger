<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;

class PermissionsSyncRequest extends FormRequest
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
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['exists:permissions,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'permissions.*' => 'permission',
        ];
    }

    public function toDto(): \App\DTOs\Tenant\SyncPermissionsDto
    {
        /** @var list<int|string> $rawIds */
        $rawIds = (array) ($this->input('permissions', []) ?? []);

        return new \App\DTOs\Tenant\SyncPermissionsDto(
            permissionIds: array_map(fn(int|string $v): int => (int) $v, $rawIds),
        );
    }
}
