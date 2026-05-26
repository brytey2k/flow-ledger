<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property int|null $branch_id
 * @property int|null $department_id
 * @property int|null $cancelled_at_stage_id
 */
class WorkflowInstance extends Model
{
    protected $fillable = [
        'workflow_template_id',
        'workflowable_type',
        'workflowable_id',
        'status',
        'sent_back_to_stage_id',
        'submitter_user_id',
        'branch_id',
        'department_id',
        'cancelled_at_stage_id',
    ];

    /** @return MorphTo<Model, $this> */
    public function workflowable(): MorphTo
    {
        return $this->morphTo();
    }

    /** @return BelongsTo<User, $this> */
    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitter_user_id');
    }

    /** @return BelongsTo<Branch, $this> */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /** @return BelongsTo<Department, $this> */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /** @return BelongsTo<WorkflowTemplate, $this> */
    public function template(): BelongsTo
    {
        return $this->belongsTo(WorkflowTemplate::class, 'workflow_template_id');
    }

    /** @return BelongsTo<WorkflowInstanceStage, $this> */
    public function cancelledAtStage(): BelongsTo
    {
        return $this->belongsTo(WorkflowInstanceStage::class, 'cancelled_at_stage_id');
    }

    /** @return HasMany<WorkflowInstanceStage, $this> */
    public function instanceStages(): HasMany
    {
        return $this->hasMany(WorkflowInstanceStage::class);
    }

    /** @return HasMany<WorkflowInstanceStage, $this> */
    public function activeInstanceStages(): HasMany
    {
        return $this->hasMany(WorkflowInstanceStage::class)->where('status', 'active');
    }

    public function sentBackStage(): WorkflowInstanceStage|null
    {
        if ($this->sent_back_to_stage_id === null) {
            return null;
        }

        return $this->instanceStages()->find($this->sent_back_to_stage_id);
    }

    public function isInProgress(): bool
    {
        return $this->status === 'in_progress';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }
}
