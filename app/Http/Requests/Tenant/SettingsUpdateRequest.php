<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use App\Enums\Tenant\PermissionKey;
use Illuminate\Foundation\Http\FormRequest;

class SettingsUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(PermissionKey::AccessSettings->value) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'logo_light' => ['sometimes', 'file', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
            'remove_logo_light' => ['sometimes', 'boolean'],
            'logo_dark' => ['sometimes', 'file', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
            'remove_logo_dark' => ['sometimes', 'boolean'],
            'logo_small' => ['sometimes', 'file', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
            'remove_logo_small' => ['sometimes', 'boolean'],
            'default_advance_cost_code_id' => ['nullable', 'integer', 'exists:cost_codes,id'],
            'require_expense_source_documents' => ['sometimes', 'boolean'],
            'require_retirement_source_documents' => ['sometimes', 'boolean'],
            'retirement_reminder_grace_period_days' => ['sometimes', 'integer', 'min:1'],
            'retirement_reminder_frequency_days' => ['sometimes', 'integer', 'min:1'],
            'retirement_reminder_notify_submitter' => ['sometimes', 'boolean'],
            'retirement_reminder_notify_approvers' => ['sometimes', 'boolean'],
            'retirement_reminder_notify_role_ids' => ['sometimes', 'array'],
            'retirement_reminder_notify_role_ids.*' => ['integer', 'exists:roles,id'],
        ];
    }
}
