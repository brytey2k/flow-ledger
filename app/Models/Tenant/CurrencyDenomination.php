<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use App\Enums\Tenant\CurrencyDenominationType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $currency_id
 * @property string $value
 * @property string $label
 * @property CurrencyDenominationType $type
 * @property int $sort_order
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Currency $currency
 * @property-read \Illuminate\Database\Eloquent\Collection<int, CashCountItem> $cashCountItems
 *
 * @method static \Database\Factories\Tenant\CurrencyDenominationFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurrencyDenomination newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurrencyDenomination newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurrencyDenomination query()
 *
 * @mixin \Eloquent
 */
class CurrencyDenomination extends Model
{
    /** @use HasFactory<\Database\Factories\Tenant\CurrencyDenominationFactory> */
    use HasFactory;

    protected $fillable = [
        'currency_id',
        'value',
        'label',
        'type',
        'sort_order',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'value' => 'decimal:4',
            'type' => CurrencyDenominationType::class,
            'sort_order' => 'integer',
        ];
    }

    /** @return BelongsTo<Currency, $this> */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    /** @return HasMany<CashCountItem, $this> */
    public function cashCountItems(): HasMany
    {
        return $this->hasMany(CashCountItem::class, 'denomination_id');
    }
}
