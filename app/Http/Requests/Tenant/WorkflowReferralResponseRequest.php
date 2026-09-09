<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WorkflowReferralResponseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'decision' => ['required', Rule::in(['confirmed', 'concern'])],
            'comment' => [
                Rule::when($this->input('decision') === 'concern', ['required'], ['nullable']),
                'string',
                'max:2000',
            ],
        ];
    }

    public function decision(): string
    {
        return $this->string('decision')->toString();
    }

    public function comment(): string|null
    {
        return $this->filled('comment') ? $this->string('comment')->toString() : null;
    }
}
