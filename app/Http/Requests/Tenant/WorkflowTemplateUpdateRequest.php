<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use App\DTOs\Tenant\WorkflowTemplateNameUpdateDto;
use Illuminate\Foundation\Http\FormRequest;

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
        ];
    }

    public function toDto(): WorkflowTemplateNameUpdateDto
    {
        return new WorkflowTemplateNameUpdateDto(
            name: $this->string('name')->toString(),
        );
    }
}
