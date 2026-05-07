<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Franzose\ClosureTable\Models\Entity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $name
 * @property string|null $code
 * @property int $level_id
 * @property int|null $currency_id
 * @property int|null $parent_id
 * @property int $position
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Level $level
 * @property-read Currency $currency
 * @property-read \Illuminate\Database\Eloquent\Collection<int, User> $users
 */
class Branch extends Entity
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = ['id', 'name', 'code', 'level_id', 'currency_id', 'parent_id', 'position'];

    protected string $closureTable = BranchClosure::class;

    /** @return BelongsTo<Level, $this> */
    public function level(): BelongsTo
    {
        return $this->belongsTo(Level::class);
    }

    /** @return BelongsTo<Currency, $this> */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    /** @return HasMany<User, $this> */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
