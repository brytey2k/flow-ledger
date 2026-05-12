<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CurrencyUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var \Illuminate\Database\Eloquent\Model $currency */
        $currency = $this->route('currency');
        $currencyKey = $currency->getKey();
        $currencyId = is_numeric($currencyKey) ? (int) $currencyKey : 0;

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('currencies', 'name')->ignore($currencyId)],
            'short_name' => ['required', 'string', 'max:10', Rule::unique('currencies', 'short_name')->ignore($currencyId)],
            'symbol' => ['required', 'string', 'max:10'],
        ];
    }

    public function toDto(): \App\DTOs\Tenant\CurrencyDto
    {
        return new \App\DTOs\Tenant\CurrencyDto(
            name: $this->string('name')->toString(),
            shortName: $this->string('short_name')->toString(),
            symbol: $this->string('symbol')->toString(),
        );
    }
}
