<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkflowParallelGroup extends Model
{
    /** @use HasFactory<\Database\Factories\Tenant\WorkflowParallelGroupFactory> */
    use HasFactory;

    protected $fillable = ['workflow_template_id', 'name', 'require_all'];

    protected function casts(): array
    {
        return [
            'require_all' => 'boolean',
        ];
    }

    /** @return BelongsTo<WorkflowTemplate, $this> */
    public function template(): BelongsTo
    {
        return $this->belongsTo(WorkflowTemplate::class, 'workflow_template_id');
    }

    /** @return HasMany<WorkflowStage, $this> */
    public function stages(): HasMany
    {
        return $this->hasMany(WorkflowStage::class, 'parallel_group_id');
    }
}
