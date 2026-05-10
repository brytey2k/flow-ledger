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
 * @property int $branch_id
 * @property int $currency_id
 * @property string $balance
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read Branch $branch
 * @property-read Currency $currency
 * @property-read \Illuminate\Database\Eloquent\Collection<int, CashbookEntry> $entries
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cashbook newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cashbook newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cashbook onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cashbook query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cashbook withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cashbook withoutTrashed()
 *
 * @mixin \Eloquent
 */
class Cashbook extends Model
{
    use HasActivity;
    /** @use HasFactory<\Illuminate\Database\Eloquent\Factories\Factory<static>> */
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'branch_id',
        'currency_id',
        'balance',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'balance' => 'decimal:2',
        ];
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

    /** @return HasMany<CashbookEntry, $this> */
    public function entries(): HasMany
    {
        return $this->hasMany(CashbookEntry::class);
    }
}
