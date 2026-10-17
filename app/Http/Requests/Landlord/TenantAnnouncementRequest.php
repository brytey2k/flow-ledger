<?php

declare(strict_types=1);

namespace App\Http\Requests\Landlord;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class TenantAnnouncementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::guard('landlord')->check();
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'announcement_message' => ['nullable', 'string', 'max:500'],
            'announcement_level' => ['nullable', Rule::in(['info', 'warning'])],
            'maintenance_mode' => ['nullable', 'boolean'],
            'maintenance_message' => ['nullable', 'string', 'max:500'],
        ];
    }
}
