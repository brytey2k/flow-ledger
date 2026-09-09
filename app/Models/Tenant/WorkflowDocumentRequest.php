<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use App\Enums\Tenant\WorkflowDocumentRequestStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property WorkflowDocumentRequestStatus $status
 * @property \Illuminate\Support\Carbon|null $submitted_at
 * @property \Illuminate\Support\Carbon|null $resolved_at
 * @property \Illuminate\Support\Carbon|null $cancelled_at
 */
class WorkflowDocumentRequest extends Model
{
    protected $fillable = [
        'workflow_instance_id',
        'opening_instance_stage_id',
        'requester_user_id',
        'opened_by_user_id',
        'parent_document_request_id',
        'status',
        'reason',
        'resolution',
        'resolution_comment',
        'submitted_at',
        'resolved_at',
        'cancelled_at',
        'cancelled_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'status' => WorkflowDocumentRequestStatus::class,
            'submitted_at' => 'datetime',
            'resolved_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<WorkflowInstance, $this> */
    public function instance(): BelongsTo
    {
        return $this->belongsTo(WorkflowInstance::class, 'workflow_instance_id');
    }

    /** @return BelongsTo<WorkflowInstanceStage, $this> */
    public function openingStage(): BelongsTo
    {
        return $this->belongsTo(WorkflowInstanceStage::class, 'opening_instance_stage_id');
    }

    /** @return BelongsTo<User, $this> */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_user_id');
    }

    /** @return BelongsTo<User, $this> */
    public function openedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'opened_by_user_id');
    }

    /** @return BelongsTo<WorkflowDocumentRequest, $this> */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_document_request_id');
    }

    /** @return HasMany<Attachment, $this> */
    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class);
    }

    /** @return HasMany<WorkflowReviewReferral, $this> */
    public function referrals(): HasMany
    {
        return $this->hasMany(WorkflowReviewReferral::class);
    }

    public function isUnresolved(): bool
    {
        return $this->status->isUnresolved();
    }
}
