<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;

class WorkflowParallelGroupStoreRequest extends FormRequest
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
            'require_all' => ['required', 'boolean'],
        ];
    }

    public function toDto(): \App\DTOs\Tenant\WorkflowParallelGroupDto
    {
        return new \App\DTOs\Tenant\WorkflowParallelGroupDto(
            name: $this->string('name')->toString(),
            requireAll: $this->boolean('require_all'),
        );
    }
}
