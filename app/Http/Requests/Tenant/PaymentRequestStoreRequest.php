<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use App\Models\Tenant\Staff;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PaymentRequestStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var \App\Models\Tenant\User $user */
        $user = $this->user();
        $profile = $user->staffProfile;

        return $profile instanceof Staff && $profile->branch_id !== null;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        $isExpense = $this->input('type') === 'expense';

        return [
            'currency_id' => ['required', 'integer', 'exists:currencies,id'],
            'type' => ['required', Rule::in(['advance', 'expense'])],
            'notes' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.amount' => ['required', 'numeric', 'min:0.01'],
            'items.*.account_code_id' => [$isExpense ? 'required' : 'nullable', 'integer', 'exists:account_codes,id'],
            'items.*.receipt_number' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function toDto(int $staffId, int $branchId): \App\DTOs\Tenant\CreatePaymentRequestDto
    {
        /** @var list<array{description: string, amount: string|float, account_code_id?: string|int|null, receipt_number?: string|null}> $rawItems */
        $rawItems = $this->input('items', []) ?? [];
        $items = array_map(
            fn(array $item): \App\DTOs\Tenant\PaymentRequestItemDto => new \App\DTOs\Tenant\PaymentRequestItemDto(
                description: (string) $item['description'],
                amount: (float) $item['amount'],
                accountCodeId: isset($item['account_code_id']) ? (int) $item['account_code_id'] : null,
                receiptNumber: isset($item['receipt_number']) ? (string) $item['receipt_number'] : null,
            ),
            $rawItems,
        );

        return new \App\DTOs\Tenant\CreatePaymentRequestDto(
            staffId: $staffId,
            branchId: $branchId,
            currencyId: $this->integer('currency_id'),
            type: $this->string('type')->toString(),
            notes: $this->filled('notes') ? $this->string('notes')->toString() : null,
            items: $items,
        );
    }
}
