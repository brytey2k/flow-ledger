<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use App\Enums\Tenant\PaymentMethod;
use App\Models\Tenant\PaymentRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class DisbursementStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        /** @var PaymentRequest|null $paymentRequest */
        $paymentRequest = $this->route('paymentRequest');
        $methodRules = ['required', new Enum(PaymentMethod::class)];

        if ($paymentRequest?->planned_disbursement_method instanceof PaymentMethod) {
            $methodRules[] = Rule::in([$paymentRequest->planned_disbursement_method->value]);
        }

        return [
            'disbursement_method' => $methodRules,
            'disbursement_reference' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function toDto(): \App\DTOs\Tenant\DisbursePaymentRequestDto
    {
        return new \App\DTOs\Tenant\DisbursePaymentRequestDto(
            method: PaymentMethod::from($this->string('disbursement_method')->toString()),
            reference: $this->filled('disbursement_reference') ? $this->string('disbursement_reference')->toString() : null,
        );
    }
}
