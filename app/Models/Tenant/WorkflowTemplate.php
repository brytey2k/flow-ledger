<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkflowTemplate extends Model
{
    /** @use HasFactory<\Database\Factories\Tenant\WorkflowTemplateFactory> */
    use HasFactory;

    protected $fillable = ['name', 'type', 'branch_id'];

    /** @return HasMany<WorkflowStage, $this> */
    public function stages(): HasMany
    {
        return $this->hasMany(WorkflowStage::class)->orderBy('display_order');
    }

    /** @return HasMany<WorkflowParallelGroup, $this> */
    public function parallelGroups(): HasMany
    {
        return $this->hasMany(WorkflowParallelGroup::class);
    }

    /** @return HasMany<WorkflowInstance, $this> */
    public function instances(): HasMany
    {
        return $this->hasMany(WorkflowInstance::class);
    }

    /** @return BelongsTo<Branch, $this> */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function hasActiveInstances(): bool
    {
        return $this->instances()->where('status', 'in_progress')->exists();
    }

    public static function resolveForBranch(string $type, int|null $branchId): self
    {
        if ($branchId !== null) {
            $branchTemplate = self::where('type', $type)->where('branch_id', $branchId)->first();

            if ($branchTemplate instanceof self) {
                return $branchTemplate;
            }
        }

        return self::where('type', $type)->whereNull('branch_id')->firstOrFail();
    }
}
