<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkflowInstanceActorClaim extends Model
{
    protected $fillable = [
        'workflow_instance_id',
        'user_id',
        'workflow_instance_stage_id',
    ];

    /** @return BelongsTo<WorkflowInstance, $this> */
    public function instance(): BelongsTo
    {
        return $this->belongsTo(WorkflowInstance::class, 'workflow_instance_id');
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<WorkflowInstanceStage, $this> */
    public function instanceStage(): BelongsTo
    {
        return $this->belongsTo(WorkflowInstanceStage::class, 'workflow_instance_stage_id');
    }
}
