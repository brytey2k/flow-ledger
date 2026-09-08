<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use App\Models\Role;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkflowInstanceStageRecoveryRole extends Model
{
    protected $fillable = [
        'workflow_instance_stage_id',
        'role_id',
        'applied_by_user_id',
        'prepared_workflow_template_id',
        'reason',
        'template_repair_status',
        'template_repair_prepared_at',
        'template_repair_published_at',
    ];

    protected function casts(): array
    {
        return [
            'template_repair_prepared_at' => 'datetime',
            'template_repair_published_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<WorkflowInstanceStage, $this> */
    public function instanceStage(): BelongsTo
    {
        return $this->belongsTo(WorkflowInstanceStage::class, 'workflow_instance_stage_id');
    }

    /** @return BelongsTo<Role, $this> */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /** @return BelongsTo<User, $this> */
    public function appliedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'applied_by_user_id');
    }

    /** @return BelongsTo<WorkflowTemplate, $this> */
    public function preparedTemplate(): BelongsTo
    {
        return $this->belongsTo(WorkflowTemplate::class, 'prepared_workflow_template_id');
    }
}
