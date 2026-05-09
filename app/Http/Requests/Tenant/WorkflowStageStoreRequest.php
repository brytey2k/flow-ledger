<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;

class WorkflowStageStoreRequest extends FormRequest
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
        ];
    }

    public function toDto(): \App\DTOs\Tenant\WorkflowStageDto
    {
        /** @var list<int|string> $rawRoleIds */
        $rawRoleIds = (array) ($this->input('role_ids', []) ?? []);

        return new \App\DTOs\Tenant\WorkflowStageDto(
            name: $this->string('name')->toString(),
            displayOrder: $this->integer('display_order'),
            skipBelowAmount: $this->filled('skip_below_amount') ? (float) $this->string('skip_below_amount')->toString() : null,
            parallelGroupId: $this->filled('parallel_group_id') ? $this->integer('parallel_group_id') : null,
            roleIds: array_map(fn(int|string $v): int => (int) $v, $rawRoleIds),
        );
    }
}
