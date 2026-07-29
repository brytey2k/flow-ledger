<?php

declare(strict_types=1);

namespace App\DTOs\Tenant;

use App\Models\Tenant\WorkflowTemplate;

readonly class WorkflowForkResult
{
    /**
     * @param WorkflowTemplate $newTemplate
     * @param array<int, int> $stageIdMap old stage id => new (cloned) stage id
     * @param array<int, int> $groupIdMap old parallel group id => new (cloned) group id
     */
    public function __construct(
        public WorkflowTemplate $newTemplate,
        public array $stageIdMap,
        public array $groupIdMap,
    ) {}
}
