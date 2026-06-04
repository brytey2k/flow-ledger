<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $branch_id
 * @property string $threshold_amount
 * @property array<int>|null $notification_user_ids
 * @property int $cooldown_minutes
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Branch $branch
 * @property-read \Illuminate\Database\Eloquent\Collection<int, CashBalanceNotificationLog> $notificationLogs
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CashBalanceThreshold newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CashBalanceThreshold newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CashBalanceThreshold query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CashBalanceThreshold active()
 *
 * @mixin \Eloquent
 */
class CashBalanceThreshold extends Model
{
    /** @use HasFactory<\Database\Factories\Tenant\CashBalanceThresholdFactory> */
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'threshold_amount',
        'notification_user_ids',
        'cooldown_minutes',
        'is_active',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'threshold_amount' => 'decimal:2',
            'notification_user_ids' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /** @return BelongsTo<Branch, $this> */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /** @return HasMany<CashBalanceNotificationLog, $this> */
    public function notificationLogs(): HasMany
    {
        return $this->hasMany(CashBalanceNotificationLog::class);
    }

    /** @param \Illuminate\Database\Eloquent\Builder<static> $query */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
