<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use App\DTOs\Tenant\CurrencyDenominationDto;
use App\Models\Tenant\Currency;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CurrencyDenominationStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        /** @var Currency $currency */
        $currency = $this->route('currency');

        return [
            'value' => [
                'required',
                'numeric',
                'min:0.001',
                'max:999999.9999',
                Rule::unique('currency_denominations')->where('currency_id', $currency->id),
            ],
            'label' => ['required', 'string', 'max:100'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function toDto(): CurrencyDenominationDto
    {
        /** @var Currency $currency */
        $currency = $this->route('currency');

        return new CurrencyDenominationDto(
            currencyId: $currency->id,
            value: (string) $this->input('value'),
            label: (string) $this->input('label'),
            sortOrder: (int) ($this->input('sort_order') ?? 0),
        );
    }
}
