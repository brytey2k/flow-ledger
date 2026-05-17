<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WorkflowTemplateStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'type' => ['required', Rule::in(['advance', 'expense', 'retirement'])],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
        ];
    }

    public function toDto(): \App\DTOs\Tenant\WorkflowTemplateDto
    {
        return new \App\DTOs\Tenant\WorkflowTemplateDto(
            name: $this->string('name')->toString(),
            type: $this->string('type')->toString(),
            branchId: $this->integer('branch_id') ?: null,
        );
    }
}
