<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use App\Models\Tenant\WorkflowInstanceStage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class WorkflowStageRecoveryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'role_id' => ['required', 'integer', 'exists:roles,id'],
            'reason' => ['required', 'string', 'max:1000'],
        ];
    }

    /** @return array<int, callable(Validator): void> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $instanceStage = $this->instanceStage();

            if (! $instanceStage instanceof WorkflowInstanceStage || ! $instanceStage->isBlocked()) {
                $validator->errors()->add('role_id', __('workflows.separation.recovery_stage_not_blocked'));

                return;
            }

            $roleId = $this->integer('role_id');
            $alreadyConfigured = $instanceStage->stage()
                ->where(function ($stage) use ($roleId): void {
                    $stage->whereHas('roles', fn($roles) => $roles->whereKey($roleId))
                        ->orWhereHas('fallbackRoles', fn($roles) => $roles->whereKey($roleId));
                })
                ->exists();

            if ($alreadyConfigured) {
                $validator->errors()->add('role_id', __('workflows.separation.recovery_role_already_configured'));
            }
        }];
    }

    public function roleId(): int
    {
        return $this->integer('role_id');
    }

    public function reason(): string
    {
        return $this->string('reason')->toString();
    }

    private function instanceStage(): WorkflowInstanceStage|null
    {
        $instanceStage = $this->route('instanceStage') ?? $this->route('workflowInstanceStage');

        return $instanceStage instanceof WorkflowInstanceStage ? $instanceStage : null;
    }
}
