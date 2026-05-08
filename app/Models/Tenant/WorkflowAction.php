<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkflowAction extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'workflow_instance_stage_id',
        'user_id',
        'action',
        'comment',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<WorkflowInstanceStage, $this> */
    public function instanceStage(): BelongsTo
    {
        return $this->belongsTo(WorkflowInstanceStage::class, 'workflow_instance_stage_id');
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
