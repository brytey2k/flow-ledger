<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ApprovalActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'action' => ['required', Rule::in(['approve', 'reject', 'send_back'])],
            'comment' => [
                Rule::when(
                    in_array($this->input('action'), ['reject', 'send_back'], true),
                    ['required', 'string', 'max:1000'],
                    ['nullable', 'string', 'max:1000'],
                ),
            ],
        ];
    }

    public function toDto(): \App\DTOs\Tenant\ApprovalActionDto
    {
        return new \App\DTOs\Tenant\ApprovalActionDto(
            action: $this->string('action')->toString(),
            comment: $this->filled('comment') ? $this->string('comment')->toString() : null,
        );
    }
}
