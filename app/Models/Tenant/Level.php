<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $name
 * @property int $position
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Franzose\ClosureTable\Extensions\Collection<int, Branch> $branches
 * @property-read int|null $branches_count
 */
class Level extends Model
{
    /** @use HasFactory<\Database\Factories\Tenant\LevelFactory> */
    use HasFactory;
    use SoftDeletes;

    /** @var list<string> */
    protected $fillable = ['name', 'position'];

    /** @return HasMany<Branch, $this> */
    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
    }

    public function getNextLevel(): self|null
    {
        return self::where('position', '>', $this->position)->orderBy('position')->first();
    }

    public function getPreviousLevel(): self|null
    {
        return self::where('position', '<', $this->position)->orderByDesc('position')->first();
    }

    /** @return EloquentCollection<int, self> */
    public function levelsBelow(): EloquentCollection
    {
        return self::where('position', '>', $this->position)->orderBy('position')->get();
    }

    public function isFirst(): bool
    {
        return self::orderBy('position')->first()?->id === $this->id;
    }

    public static function atPosition(int $position): self|null
    {
        return self::where('position', $position)->first();
    }

    public function isPenultimate(): bool
    {
        $last = self::orderByDesc('position')->first();

        return $last && self::where('position', '<', $last->position)->orderByDesc('position')->first()?->id === $this->id;
    }

    public function isBeforeOrAtLevel(self $other): bool
    {
        return $this->position <= $other->position;
    }

    public function isBeforeLevel(self $other): bool
    {
        return $this->position < $other->position;
    }
}
