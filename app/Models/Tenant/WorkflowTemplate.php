<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkflowTemplate extends Model
{
    /** @use HasFactory<\Database\Factories\Tenant\WorkflowTemplateFactory> */
    use HasFactory;

    protected $fillable = ['name', 'type'];

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
}
