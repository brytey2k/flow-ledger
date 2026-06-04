<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CashBalanceThresholdUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'threshold_amount' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'notification_user_ids' => ['nullable', 'array'],
            'notification_user_ids.*' => ['integer', Rule::exists('users', 'id')],
            'cooldown_minutes' => ['required', 'integer', 'min:0', 'max:10080'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
