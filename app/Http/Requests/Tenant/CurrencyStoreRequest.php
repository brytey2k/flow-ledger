<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;

class CurrencyStoreRequest extends FormRequest
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
        return [
            'name' => ['required', 'string', 'max:255', 'unique:currencies,name'],
            'short_name' => ['required', 'string', 'max:10', 'unique:currencies,short_name'],
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
