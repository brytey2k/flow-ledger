<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\HasActivity;

/**
 * @property \Carbon\Carbon|null $submitted_at
 * @property \Carbon\Carbon|null $approved_at
 * @property \Carbon\Carbon|null $disbursed_at
 */
class PaymentRequest extends Model
{
    use HasActivity;
    /** @use HasFactory<\Database\Factories\Tenant\PaymentRequestFactory> */
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'staff_id',
        'branch_id',
        'currency_id',
        'type',
        'status',
        'total_amount',
        'notes',
        'submitted_at',
        'approved_at',
        'disbursed_at',
        'disbursed_by_user_id',
        'disbursement_method',
        'disbursement_reference',
    ];

    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:2',
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
            'disbursed_at' => 'datetime',
            'disbursement_method' => \App\Enums\Tenant\PaymentMethod::class,
        ];
    }

    /** @return BelongsTo<Staff, $this> */
    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    /** @return BelongsTo<Branch, $this> */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /** @return BelongsTo<Currency, $this> */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    /** @return BelongsTo<User, $this> */
    public function disbursedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'disbursed_by_user_id');
    }

    /** @return HasMany<PaymentRequestItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(PaymentRequestItem::class);
    }

    /** @return HasOne<RetirementRequest, $this> */
    public function retirementRequest(): HasOne
    {
        return $this->hasOne(RetirementRequest::class);
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

    /** @return MorphMany<CashbookEntry, $this> */
    public function cashbookEntries(): MorphMany
    {
        return $this->morphMany(CashbookEntry::class, 'sourceable');
    }

    public function isAdvance(): bool
    {
        return $this->type === 'advance';
    }

    public function isExpense(): bool
    {
        return $this->type === 'expense';
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
