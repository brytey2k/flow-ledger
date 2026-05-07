<?php

declare(strict_types=1);

namespace App\Http\Requests\Landlord;

use App\Enums\FeatureFlag;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkFeatureFlagUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
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
