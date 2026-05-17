<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;

class RetirementRequestStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'notes' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.amount' => ['required', 'numeric', 'min:0.01'],
            'items.*.cost_code_id' => ['required', 'integer', 'exists:cost_codes,id'],
            'items.*.receipt_number' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function toDto(): \App\DTOs\Tenant\CreateRetirementRequestDto
    {
        /** @var list<array{description: string, amount: string|float, cost_code_id: string|int, receipt_number?: string|null}> $rawItems */
        $rawItems = $this->input('items', []) ?? [];
        $items = array_map(
            fn(array $item): \App\DTOs\Tenant\RetirementRequestItemDto => new \App\DTOs\Tenant\RetirementRequestItemDto(
                description: (string) $item['description'],
                amount: (float) $item['amount'],
                costCodeId: (int) $item['cost_code_id'],
                receiptNumber: isset($item['receipt_number']) ? (string) $item['receipt_number'] : null,
            ),
            $rawItems,
        );

        return new \App\DTOs\Tenant\CreateRetirementRequestDto(
            notes: $this->filled('notes') ? $this->string('notes')->toString() : null,
            items: $items,
        );
    }
}
