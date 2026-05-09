<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;

class BranchStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50', 'unique:branches,code'],
            'level_id' => ['required', 'integer', 'exists:levels,id'],
            'currency_id' => ['required', 'integer', 'exists:currencies,id'],
            'parent_id' => ['nullable', 'integer', 'exists:branches,id'],
            'position' => ['required', 'integer', 'min:1'],
        ];
    }

    public function toDto(): \App\DTOs\Tenant\BranchDto
    {
        return new \App\DTOs\Tenant\BranchDto(
            name: $this->string('name')->toString(),
            code: $this->filled('code') ? $this->string('code')->toString() : null,
            levelId: $this->integer('level_id'),
            currencyId: $this->integer('currency_id'),
            parentId: $this->filled('parent_id') ? $this->integer('parent_id') : null,
            position: $this->integer('position'),
        );
    }
}
