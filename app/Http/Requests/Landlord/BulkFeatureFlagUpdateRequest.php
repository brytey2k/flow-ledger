<?php

declare(strict_types=1);

namespace App\Http\Requests\Landlord;

use App\Enums\FeatureFlag;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class BulkFeatureFlagUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::guard('landlord')->check();
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'flag' => ['required', 'string', Rule::in(array_column(FeatureFlag::cases(), 'value'))],
            'action' => ['required', Rule::in(['enable', 'disable'])],
        ];
    }
}
