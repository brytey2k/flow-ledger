<?php

declare(strict_types=1);

namespace App\Http\Requests\Landlord;

use App\Enums\TenantFixUp;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class TenantFixUpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::guard('landlord')->check();
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        $isReminders = $this->route('fixUp') === TenantFixUp::ResendRetirementReminders;

        return [
            'kind' => [Rule::requiredIf($this->route('fixUp') === TenantFixUp::ForceCancelRequest), 'nullable', Rule::in(['payment', 'retirement'])],
            'record_id' => [$isReminders ? 'nullable' : 'required', 'integer', 'min:1'],
        ];
    }
}
