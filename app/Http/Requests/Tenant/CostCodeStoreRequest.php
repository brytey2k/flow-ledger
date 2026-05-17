<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;

class CostCodeStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:50', 'unique:cost_codes,code'],
            'name' => ['required', 'string', 'max:150'],
            'department_id' => ['required', 'integer', 'exists:departments,id'],
        ];
    }

    public function toDto(): \App\DTOs\Tenant\CostCodeDto
    {
        return new \App\DTOs\Tenant\CostCodeDto(
            code: $this->string('code')->toString(),
            name: $this->string('name')->toString(),
            departmentId: $this->integer('department_id'),
        );
    }
}
