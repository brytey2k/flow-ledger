<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class RetirementRequestItem extends Model
{
    /** @use HasFactory<\Database\Factories\Tenant\RetirementRequestItemFactory> */
    use HasFactory;

    protected $fillable = [
        'retirement_request_id',
        'cost_code_id',
        'description',
        'amount',
        'receipt_number',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
        ];
    }

    /** @return BelongsTo<RetirementRequest, $this> */
    public function retirementRequest(): BelongsTo
    {
        return $this->belongsTo(RetirementRequest::class);
    }

    /** @return BelongsTo<CostCode, $this> */
    public function costCode(): BelongsTo
    {
        return $this->belongsTo(CostCode::class);
    }

    /** @return MorphMany<Attachment, $this> */
    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }
}
