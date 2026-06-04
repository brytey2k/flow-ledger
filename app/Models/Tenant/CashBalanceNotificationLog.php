<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $cash_balance_threshold_id
 * @property string $balance_amount
 * @property \Illuminate\Support\Carbon $notified_at
 * @property-read CashBalanceThreshold $threshold
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CashBalanceNotificationLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CashBalanceNotificationLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CashBalanceNotificationLog query()
 *
 * @mixin \Eloquent
 */
class CashBalanceNotificationLog extends Model
{
    /** @use HasFactory<\Database\Factories\Tenant\CashBalanceNotificationLogFactory> */
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'cash_balance_threshold_id',
        'balance_amount',
        'notified_at',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'balance_amount' => 'decimal:2',
            'notified_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<CashBalanceThreshold, $this> */
    public function threshold(): BelongsTo
    {
        return $this->belongsTo(CashBalanceThreshold::class, 'cash_balance_threshold_id');
    }
}
