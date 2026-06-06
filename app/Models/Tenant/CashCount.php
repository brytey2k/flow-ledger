<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\HasActivity;

/**
 * @property int $id
 * @property int $cashbook_id
 * @property int $counted_by_user_id
 * @property \Illuminate\Support\Carbon $counted_at
 * @property string $cashbook_balance_at_count
 * @property string $counted_total
 * @property string $difference
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Cashbook $cashbook
 * @property-read User $countedBy
 * @property-read \Illuminate\Database\Eloquent\Collection<int, CashCountItem> $items
 *
 * @method static \Database\Factories\Tenant\CashCountFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CashCount newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CashCount newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CashCount onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CashCount query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CashCount withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CashCount withoutTrashed()
 *
 * @mixin \Eloquent
 */
class CashCount extends Model
{
    use HasActivity;
    /** @use HasFactory<\Illuminate\Database\Eloquent\Factories\Factory<static>> */
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'cashbook_id',
        'counted_by_user_id',
        'counted_at',
        'cashbook_balance_at_count',
        'counted_total',
        'difference',
        'notes',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'counted_at' => 'datetime',
            'cashbook_balance_at_count' => 'decimal:2',
            'counted_total' => 'decimal:2',
            'difference' => 'decimal:2',
        ];
    }

    public function status(): string
    {
        $diff = abs((float) $this->difference);

        if ($diff <= 0.01) {
            return 'equal';
        }

        return (float) $this->difference > 0 ? 'surplus' : 'deficit';
    }

    /** @return BelongsTo<Cashbook, $this> */
    public function cashbook(): BelongsTo
    {
        return $this->belongsTo(Cashbook::class);
    }

    /** @return BelongsTo<User, $this> */
    public function countedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'counted_by_user_id');
    }

    /** @return HasMany<CashCountItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(CashCountItem::class);
    }
}
