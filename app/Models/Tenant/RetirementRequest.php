<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\HasActivity;

class RetirementRequest extends Model
{
    use HasActivity;
    /** @use HasFactory<\Database\Factories\Tenant\RetirementRequestFactory> */
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'payment_request_id',
        'status',
        'total_amount_expended',
        'difference_amount',
        'difference_type',
        'notes',
        'submitted_at',
        'approved_at',
        'settled_at',
        'settled_by_user_id',
        'settlement_notes',
    ];

    protected function casts(): array
    {
        return [
            'total_amount_expended' => 'decimal:2',
            'difference_amount' => 'decimal:2',
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
            'settled_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function settledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'settled_by_user_id');
    }

    /** @return BelongsTo<PaymentRequest, $this> */
    public function paymentRequest(): BelongsTo
    {
        return $this->belongsTo(PaymentRequest::class);
    }

    /** @return HasMany<RetirementRequestItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(RetirementRequestItem::class);
    }

    /** @return MorphMany<Attachment, $this> */
    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    /** @return MorphMany<WorkflowInstance, $this> */
    public function workflowInstances(): MorphMany
    {
        return $this->morphMany(WorkflowInstance::class, 'workflowable');
    }

    /** @return MorphOne<WorkflowInstance, $this> */
    public function activeWorkflowInstance(): MorphOne
    {
        return $this->morphOne(WorkflowInstance::class, 'workflowable')
            ->where('status', 'in_progress');
    }

    /** @return MorphMany<Comment, $this> */
    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable')->latest();
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isSentBack(): bool
    {
        return $this->status === 'sent_back';
    }
}
