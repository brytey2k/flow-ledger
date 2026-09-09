<?php

declare(strict_types=1);

namespace App\DTOs\Tenant;

readonly class WorkflowStageDto
{
    /**
     * @param string $name
     * @param int $displayOrder
     * @param float|null $skipBelowAmount
     * @param int|null $parallelGroupId
     * @param array<int, int> $roleIds
     * @param array<int, int> $fallbackRoleIds
     * @param bool $scopeToDepartment
     * @param bool $scopeToBranch
     * @param bool $allowSendBack
     * @param bool $allowDocumentRequests
     */
    public function __construct(
        public string $name,
        public int $displayOrder,
        public float|null $skipBelowAmount,
        public int|null $parallelGroupId,
        public array $roleIds,
        public array $fallbackRoleIds = [],
        public bool $scopeToDepartment = false,
        public bool $scopeToBranch = false,
        public bool $allowSendBack = true,
        public bool $allowDocumentRequests = true,
    ) {}
}
