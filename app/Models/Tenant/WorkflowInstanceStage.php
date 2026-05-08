<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkflowInstanceStage extends Model
{
    protected $fillable = [
        'workflow_instance_id',
        'workflow_stage_id',
        'status',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<WorkflowInstance, $this> */
    public function instance(): BelongsTo
    {
        return $this->belongsTo(WorkflowInstance::class, 'workflow_instance_id');
    }

    /** @return BelongsTo<WorkflowStage, $this> */
    public function stage(): BelongsTo
    {
        return $this->belongsTo(WorkflowStage::class, 'workflow_stage_id');
    }

    /** @return HasMany<WorkflowAction, $this> */
    public function actions(): HasMany
    {
        return $this->hasMany(WorkflowAction::class)->latest();
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isTerminal(): bool
    {
        return in_array($this->status, ['approved', 'rejected', 'sent_back', 'skipped', 'cancelled'], true);
    }
}
