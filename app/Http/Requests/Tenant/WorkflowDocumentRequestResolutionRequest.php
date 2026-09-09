<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WorkflowDocumentRequestResolutionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'resolution' => ['required', Rule::in(['resume', 'request_more_documents', 'send_back', 'reject', 'override'])],
            'comment' => [
                Rule::when(in_array($this->input('resolution'), ['request_more_documents', 'send_back', 'reject', 'override'], true), ['required'], ['nullable']),
                'string',
                'max:2000',
            ],
        ];
    }

    public function resolution(): string
    {
        return $this->string('resolution')->toString();
    }

    public function comment(): string|null
    {
        return $this->filled('comment') ? $this->string('comment')->toString() : null;
    }
}
