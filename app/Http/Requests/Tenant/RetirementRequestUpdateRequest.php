<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use App\DTOs\Tenant\CreateRetirementRequestDto;
use App\DTOs\Tenant\RetirementRequestItemDto;
use Illuminate\Foundation\Http\FormRequest;

class RetirementRequestUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $didNotSpendMoney = $this->boolean('did_not_spend_money');

        $rules = [
            'notes' => ['nullable', 'string', 'max:2000'],
            'did_not_spend_money' => ['required', 'boolean'],
            'items' => $didNotSpendMoney ? ['nullable', 'array'] : ['required', 'array', 'min:1'],
        ];

        if (! $didNotSpendMoney) {
            $rules['items.*.description'] = ['required', 'string', 'max:255'];
            $rules['items.*.amount'] = ['required', 'numeric', 'min:0.01'];
            $rules['items.*.cost_code_id'] = ['required', 'integer', 'exists:cost_codes,id'];
            $rules['items.*.receipt_number'] = ['nullable', 'string', 'max:100'];
        }

        return $rules;
    }

    public function toDto(): CreateRetirementRequestDto
    {
        $didNotSpendMoney = $this->boolean('did_not_spend_money');

        /** @var list<array{description: string, amount: string|float, cost_code_id: string|int, receipt_number?: string|null}> $rawItems */
        $rawItems = $didNotSpendMoney ? [] : ($this->input('items', []) ?? []);
        $items = array_map(
            fn(array $item): RetirementRequestItemDto => new RetirementRequestItemDto(
                description: (string) $item['description'],
                amount: (float) $item['amount'],
                costCodeId: (int) $item['cost_code_id'],
                receiptNumber: isset($item['receipt_number']) ? (string) $item['receipt_number'] : null,
            ),
            $rawItems,
        );

        return new CreateRetirementRequestDto(
            notes: $this->filled('notes') ? $this->string('notes')->toString() : null,
            didNotSpendMoney: $didNotSpendMoney,
            items: $items,
        );
    }

    public function attributes(): array
    {
        return [
            'did_not_spend_money' => __('retirements.fields.did_not_spend_money'),
        ];
    }
}
