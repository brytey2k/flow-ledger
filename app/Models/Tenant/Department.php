<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $name
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, CostCode> $costCodes
 * @property-read int|null $cost_codes_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Staff> $staff
 * @property-read int|null $staff_count
 */
class Department extends Model
{
    /** @use HasFactory<\Database\Factories\Tenant\DepartmentFactory> */
    use HasFactory;
    use SoftDeletes;

    /** @var list<string> */
    protected $fillable = ['name'];

    /** @return HasMany<CostCode, $this> */
    public function costCodes(): HasMany
    {
        return $this->hasMany(CostCode::class);
    }

    /** @return HasMany<Staff, $this> */
    public function staff(): HasMany
    {
        return $this->hasMany(Staff::class);
    }
}
