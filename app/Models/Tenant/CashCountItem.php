<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $cash_count_id
 * @property int $denomination_id
 * @property string $denomination_value
 * @property string $denomination_label
 * @property int $quantity
 * @property string $subtotal
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read CashCount $cashCount
 * @property-read CurrencyDenomination $denomination
 *
 * @method static \Database\Factories\Tenant\CashCountItemFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CashCountItem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CashCountItem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CashCountItem query()
 *
 * @mixin \Eloquent
 */
class CashCountItem extends Model
{
    /** @use HasFactory<\Database\Factories\Tenant\CashCountItemFactory> */
    use HasFactory;

    protected $fillable = [
        'cash_count_id',
        'denomination_id',
        'denomination_value',
        'denomination_label',
        'quantity',
        'subtotal',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'denomination_value' => 'decimal:4',
            'quantity' => 'integer',
            'subtotal' => 'decimal:2',
        ];
    }

    /** @return BelongsTo<CashCount, $this> */
    public function cashCount(): BelongsTo
    {
        return $this->belongsTo(CashCount::class);
    }

    /** @return BelongsTo<CurrencyDenomination, $this> */
    public function denomination(): BelongsTo
    {
        return $this->belongsTo(CurrencyDenomination::class, 'denomination_id');
    }
}
