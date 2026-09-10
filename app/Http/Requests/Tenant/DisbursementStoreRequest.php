<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;

class DisbursementStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'disbursement_method' => ['prohibited'],
            'disbursement_reference' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function toDto(): \App\DTOs\Tenant\DisbursePaymentRequestDto
    {
        return new \App\DTOs\Tenant\DisbursePaymentRequestDto(
            reference: $this->filled('disbursement_reference') ? $this->string('disbursement_reference')->toString() : null,
        );
    }
}
