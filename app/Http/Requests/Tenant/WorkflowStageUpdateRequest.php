<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class WorkflowStageUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'display_order' => ['required', 'integer', 'min:1', 'max:999'],
            'skip_below_amount' => ['nullable', 'numeric', 'min:0'],
            'parallel_group_id' => ['nullable', 'integer', 'exists:workflow_parallel_groups,id'],
            'role_ids' => ['required', 'array', 'min:1'],
            'role_ids.*' => ['integer', 'exists:roles,id'],
            'fallback_role_ids' => ['nullable', 'array'],
            'fallback_role_ids.*' => ['integer', 'exists:roles,id'],
            'scope_to_department' => ['nullable', 'boolean'],
            'scope_to_branch' => ['nullable', 'boolean'],
            'allow_send_back' => ['nullable', 'boolean'],
            'allow_document_requests' => ['nullable', 'boolean'],
        ];
    }

    /** @return array<int, callable(Validator): void> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            /** @var list<int|string> $primaryInput */
            $primaryInput = (array) $this->input('role_ids', []);
            /** @var list<int|string> $fallbackInput */
            $fallbackInput = (array) $this->input('fallback_role_ids', []);
            $primary = array_map(static fn(int|string $value): int => (int) $value, $primaryInput);
            $fallback = array_map(static fn(int|string $value): int => (int) $value, $fallbackInput);
            if (array_intersect($primary, $fallback) !== []) {
                $validator->errors()->add('fallback_role_ids', __('workflows.stages.validation.fallback_must_differ'));
            }
        }];
    }

    public function toDto(): \App\DTOs\Tenant\WorkflowStageDto
    {
        /** @var list<int|string> $rawRoleIds */
        $rawRoleIds = (array) ($this->input('role_ids', []) ?? []);
        /** @var list<int|string> $rawFallbackRoleIds */
        $rawFallbackRoleIds = (array) ($this->input('fallback_role_ids', []) ?? []);

        return new \App\DTOs\Tenant\WorkflowStageDto(
            name: $this->string('name')->toString(),
            displayOrder: $this->integer('display_order'),
            skipBelowAmount: $this->filled('skip_below_amount') ? (float) $this->string('skip_below_amount')->toString() : null,
            parallelGroupId: $this->filled('parallel_group_id') ? $this->integer('parallel_group_id') : null,
            roleIds: array_map(fn(int|string $v): int => (int) $v, $rawRoleIds),
            fallbackRoleIds: array_map(fn(int|string $v): int => (int) $v, $rawFallbackRoleIds),
            scopeToDepartment: (bool) $this->input('scope_to_department', false),
            scopeToBranch: (bool) $this->input('scope_to_branch', false),
            allowSendBack: (bool) $this->input('allow_send_back', true),
            allowDocumentRequests: (bool) $this->input('allow_document_requests', true),
        );
    }
}
