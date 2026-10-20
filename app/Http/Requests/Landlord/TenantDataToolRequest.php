<?php

declare(strict_types=1);

namespace App\Http\Requests\Landlord;

use App\DTOs\Landlord\TenantDataToolFilters;
use App\Enums\TenantDataTool;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class TenantDataToolRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::guard('landlord')->check();
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        $tool = $this->route('tool');

        return [
            'confirm_tenant_name' => ['nullable', 'string'],
            'statuses' => ['nullable', 'array'],
            'statuses.*' => ['string', Rule::in($tool instanceof TenantDataTool ? $tool->statusOptions() : [])],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'keep_drafts' => ['nullable', 'boolean'],
        ];
    }

    public function filters(): TenantDataToolFilters
    {
        /** @var array{statuses?: list<string>, from?: string|null, to?: string|null, keep_drafts?: bool} $data */
        $data = $this->safe()->only(['statuses', 'from', 'to', 'keep_drafts']);

        return TenantDataToolFilters::fromArray($data);
    }
}
