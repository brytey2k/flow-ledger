<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WorkflowTemplateUpdateRequest extends FormRequest
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
        ];
    }

    public function toDto(): \App\DTOs\Tenant\WorkflowTemplateDto
    {
        return new \App\DTOs\Tenant\WorkflowTemplateDto(
            name: $this->string('name')->toString(),
            type: $this->string('type')->toString(),
        );
    }
}
