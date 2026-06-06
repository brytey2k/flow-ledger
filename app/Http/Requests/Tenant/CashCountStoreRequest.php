<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use App\DTOs\Tenant\CashCountDto;
use App\Models\Tenant\Cashbook;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class CashCountStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'notes' => ['nullable', 'string', 'max:5000'],
            'items' => ['required', 'array'],
            'items.*.denomination_id' => ['required', 'integer', Rule::exists('currency_denominations', 'id')],
            'items.*.quantity' => ['required', 'integer', 'min:0'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v): void {
            $items = $this->input('items', []);
            $hasPositive = collect($items)->contains(fn($item) => (int) ($item['quantity'] ?? 0) > 0);

            if (! $hasPositive) {
                $v->errors()->add('items', __('cash_count.validation.at_least_one_quantity'));
            }
        });
    }

    public function toDto(): CashCountDto
    {
        /** @var Cashbook $cashbook */
        $cashbook = $this->route('branch')->cashbook;

        /** @var array<int, array{denomination_id: string, quantity: string}> $rawItems */
        $rawItems = $this->input('items', []);

        $items = array_map(
            fn(array $item) => [
                'denomination_id' => (int) $item['denomination_id'],
                'quantity' => (int) $item['quantity'],
            ],
            $rawItems,
        );

        return new CashCountDto(
            cashbookId: $cashbook->id,
            notes: $this->input('notes'),
            items: $items,
        );
    }
}
