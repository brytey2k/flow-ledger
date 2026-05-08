<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class WorkflowInstance extends Model
{
    protected $fillable = [
        'workflow_template_id',
        'workflowable_type',
        'workflowable_id',
        'status',
        'sent_back_to_stage_id',
    ];

    /** @return MorphTo<Model, $this> */
    public function workflowable(): MorphTo
    {
        return $this->morphTo();
    }

    /** @return BelongsTo<WorkflowTemplate, $this> */
    public function template(): BelongsTo
    {
        return $this->belongsTo(WorkflowTemplate::class, 'workflow_template_id');
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
}
