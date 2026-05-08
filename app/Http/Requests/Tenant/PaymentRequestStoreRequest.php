<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PaymentRequestStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        $isExpense = $this->input('type') === 'expense';

        return [
            'staff_id' => ['required', 'integer', 'exists:staff,id'],
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
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
}
