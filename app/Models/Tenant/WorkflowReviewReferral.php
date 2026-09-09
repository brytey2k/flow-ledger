<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use App\Enums\Tenant\WorkflowReviewDecision;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkflowReviewReferral extends Model
{
    protected $fillable = [
        'workflow_document_request_id',
        'referring_instance_stage_id',
        'referred_workflow_action_id',
        'referred_instance_stage_id',
        'referred_by_user_id',
        'reviewer_user_id',
        'decision',
        'comment',
        'responded_at',
    ];

    protected function casts(): array
    {
        return [
            'decision' => WorkflowReviewDecision::class,
            'responded_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<WorkflowDocumentRequest, $this> */
    public function documentRequest(): BelongsTo
    {
        return $this->belongsTo(WorkflowDocumentRequest::class, 'workflow_document_request_id');
    }

    /** @return BelongsTo<WorkflowInstanceStage, $this> */
    public function referringStage(): BelongsTo
    {
        return $this->belongsTo(WorkflowInstanceStage::class, 'referring_instance_stage_id');
    }

    /** @return BelongsTo<WorkflowInstanceStage, $this> */
    public function referredStage(): BelongsTo
    {
        return $this->belongsTo(WorkflowInstanceStage::class, 'referred_instance_stage_id');
    }

    /** @return BelongsTo<WorkflowAction, $this> */
    public function referredAction(): BelongsTo
    {
        return $this->belongsTo(WorkflowAction::class, 'referred_workflow_action_id');
    }

    /** @return BelongsTo<User, $this> */
    public function referredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referred_by_user_id');
    }

    /** @return BelongsTo<User, $this> */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_user_id');
    }
}
