<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;

class DisbursementStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'disbursement_method' => ['required', 'string', 'max:100'],
            'disbursement_reference' => ['nullable', 'string', 'max:255'],
        ];
    }
}
