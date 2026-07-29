<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use App\Enums\Tenant\WorkflowTemplateStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class WorkflowTemplate extends Model
{
    /** @use HasFactory<\Database\Factories\Tenant\WorkflowTemplateFactory> */
    use HasFactory;

    protected $fillable = ['name', 'type', 'branch_id', 'template_group_id', 'version', 'is_current', 'status'];

    protected static function booted(): void
    {
        static::creating(function (self $template): void {
            $template->template_group_id ??= (string) Str::uuid();
        });
    }

    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'is_current' => 'boolean',
        ];
    }

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

    /** @return HasMany<WorkflowTemplate, $this> */
    public function versions(): HasMany
    {
        return $this->hasMany(self::class, 'template_group_id', 'template_group_id')->orderBy('version');
    }

    public function hasActiveInstances(): bool
    {
        return $this->instances()->where('status', 'in_progress')->exists();
    }

    public function isDraft(): bool
    {
        return $this->status === WorkflowTemplateStatus::Draft->value;
    }

    public function hasActiveInstancesAcrossFamily(): bool
    {
        return self::where('template_group_id', $this->template_group_id)
            ->whereHas('instances', fn($query) => $query->where('status', 'in_progress'))
            ->exists();
    }

    public static function resolveForBranch(string $type, int|null $branchId): self
    {
        if ($branchId !== null) {
            $branchTemplate = self::where('type', $type)
                ->where('branch_id', $branchId)
                ->where('is_current', true)
                ->first();

            if ($branchTemplate instanceof self) {
                return $branchTemplate;
            }
        }

        return self::where('type', $type)->whereNull('branch_id')->where('is_current', true)->firstOrFail();
    }
}
