<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use App\Models\Tenant\PaymentRequest;
use Illuminate\Foundation\Http\FormRequest;

class PaymentRequestUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        /** @var PaymentRequest|null $paymentRequest */
        $paymentRequest = $this->route('paymentRequest');
        $isExpense = $paymentRequest instanceof PaymentRequest && $paymentRequest->type === \App\Enums\Tenant\PaymentRequestType::Expense->value;

        return [
            'currency_id' => ['required', 'integer', 'exists:currencies,id'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.amount' => ['required', 'numeric', 'min:0.01'],
            'items.*.cost_code_id' => [$isExpense ? 'required' : 'nullable', 'integer', 'exists:cost_codes,id'],
            'items.*.receipt_number' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function toDto(int $staffId, int $branchId, string $type): \App\DTOs\Tenant\CreatePaymentRequestDto
    {
        /** @var list<array{description: string, amount: string|float, cost_code_id?: string|int|null, receipt_number?: string|null}> $rawItems */
        $rawItems = $this->input('items', []) ?? [];
        $items = array_map(
            fn(array $item): \App\DTOs\Tenant\PaymentRequestItemDto => new \App\DTOs\Tenant\PaymentRequestItemDto(
                description: (string) $item['description'],
                amount: (float) $item['amount'],
                costCodeId: isset($item['cost_code_id']) ? (int) $item['cost_code_id'] : null,
                receiptNumber: isset($item['receipt_number']) ? (string) $item['receipt_number'] : null,
            ),
            $rawItems,
        );

        return new \App\DTOs\Tenant\CreatePaymentRequestDto(
            staffId: $staffId,
            branchId: $branchId,
            currencyId: $this->integer('currency_id'),
            type: $type,
            notes: $this->filled('notes') ? $this->string('notes')->toString() : null,
            items: $items,
        );
    }
}
