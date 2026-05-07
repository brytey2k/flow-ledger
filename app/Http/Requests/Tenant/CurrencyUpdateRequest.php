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
        $currency = $this->route('currency');

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('currencies', 'name')->ignore($currency->id)],
            'short_name' => ['required', 'string', 'max:10', Rule::unique('currencies', 'short_name')->ignore($currency->id)],
            'symbol' => ['required', 'string', 'max:10'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Please provide a currency name.',
            'name.unique' => 'A currency with this name already exists.',
            'name.max' => 'The currency name may not exceed 255 characters.',
            'short_name.required' => 'Please provide a currency code.',
            'short_name.unique' => 'A currency with this code already exists.',
            'short_name.max' => 'The currency code may not exceed 10 characters.',
            'symbol.required' => 'Please provide a currency symbol.',
            'symbol.max' => 'The currency symbol may not exceed 10 characters.',
        ];
    }
}
