<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property bool $scope_to_department
 * @property bool $scope_to_branch
 */
class WorkflowStage extends Model
{
    /** @use HasFactory<\Database\Factories\Tenant\WorkflowStageFactory> */
    use HasFactory;

    protected $fillable = [
        'workflow_template_id',
        'parallel_group_id',
        'name',
        'display_order',
        'skip_below_amount',
        'scope_to_department',
        'scope_to_branch',
    ];

    protected function casts(): array
    {
        return [
            'skip_below_amount' => 'decimal:2',
            'display_order' => 'integer',
            'scope_to_department' => 'boolean',
            'scope_to_branch' => 'boolean',
        ];
    }

    /** @return BelongsTo<WorkflowTemplate, $this> */
    public function template(): BelongsTo
    {
        return $this->belongsTo(WorkflowTemplate::class, 'workflow_template_id');
    }

    /** @return BelongsTo<WorkflowParallelGroup, $this> */
    public function parallelGroup(): BelongsTo
    {
        return $this->belongsTo(WorkflowParallelGroup::class, 'parallel_group_id');
    }

    /** @return BelongsToMany<Role, $this> */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'workflow_stage_roles', 'workflow_stage_id', 'role_id');
    }

    /** @return HasMany<WorkflowInstanceStage, $this> */
    public function instanceStages(): HasMany
    {
        return $this->hasMany(WorkflowInstanceStage::class, 'workflow_stage_id');
    }
}
