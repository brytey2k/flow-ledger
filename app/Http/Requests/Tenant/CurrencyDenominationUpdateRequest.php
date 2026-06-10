<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use App\DTOs\Tenant\CurrencyDenominationDto;
use App\Enums\Tenant\CurrencyDenominationType;
use App\Models\Tenant\Currency;
use App\Models\Tenant\CurrencyDenomination;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class CurrencyDenominationUpdateRequest extends FormRequest
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

        /** @var CurrencyDenomination $denomination */
        $denomination = $this->route('denomination');

        $type = (string) $this->string('type') ?: $denomination->type->value;

        return [
            'value' => [
                'required',
                'numeric',
                'min:0.001',
                'max:999999.9999',
                Rule::unique('currency_denominations')
                    ->where('currency_id', $currency->id)
                    ->where('type', $type)
                    ->ignore($denomination->id),
            ],
            'label' => ['required', 'string', 'max:100'],
            'type' => ['required', new Enum(CurrencyDenominationType::class)],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function toDto(): CurrencyDenominationDto
    {
        /** @var Currency $currency */
        $currency = $this->route('currency');

        return new CurrencyDenominationDto(
            currencyId: $currency->id,
            value: (string) $this->string('value'),
            label: (string) $this->string('label'),
            type: CurrencyDenominationType::from((string) $this->string('type')),
            sortOrder: $this->integer('sort_order'),
        );
    }
}
