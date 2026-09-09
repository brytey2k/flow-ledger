<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;

class WorkflowReferralStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'workflow_action_ids' => ['required', 'array', 'min:1'],
            'workflow_action_ids.*' => ['required', 'integer', 'distinct', 'exists:workflow_actions,id'],
        ];
    }

    /** @return list<int> */
    public function actionIds(): array
    {
        /** @var list<int|string> $ids */
        $ids = (array) $this->input('workflow_action_ids', []);

        return array_map(static fn(int|string $id): int => (int) $id, $ids);
    }
}
