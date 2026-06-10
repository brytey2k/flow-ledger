<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use App\DTOs\Tenant\CurrencyDenominationDto;
use App\Enums\Tenant\CurrencyDenominationType;
use App\Models\Tenant\Currency;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

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

        $type = (string) $this->string('type') ?: CurrencyDenominationType::Note->value;

        return [
            'value' => [
                'required',
                'numeric',
                'min:0.001',
                'max:999999.9999',
                Rule::unique('currency_denominations')
                    ->where('currency_id', $currency->id)
                    ->where('type', $type),
            ],
            'label' => ['required', 'string', 'max:100'],
            'type' => ['required', new Enum(CurrencyDenominationType::class)],
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
        );
    }
}
