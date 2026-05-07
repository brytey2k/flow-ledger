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
}
