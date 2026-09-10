<?php

declare(strict_types=1);

use App\Http\Controllers\Web\Landlord\TenantsController;
use App\Http\Controllers\Web\Tenant\CashBalanceThresholdController;
use App\Repositories\ActivityLogRepository;
use App\Repositories\BranchRepository;
use App\Repositories\CashbookRepository;
use App\Repositories\CashCountRepository;
use App\Repositories\CostCodeRepository;
use App\Repositories\CurrencyDenominationRepository;
use App\Repositories\CurrencyRepository;
use App\Repositories\DepartmentRepository;
use App\Repositories\LevelRepository;
use App\Repositories\PaymentRequestRepository;
use App\Repositories\PositionRepository;
use App\Repositories\RetirementRequestRepository;
use App\Repositories\RoleRepository;
use App\Repositories\StaffRepository;
use App\Repositories\UserRepository;
use App\Repositories\WorkflowInstanceRepository;
use App\Repositories\WorkflowTemplateRepository;
use App\Services\LandlordTenantAccessControlService;
use App\Services\WorkflowDocumentRequestService;

uses(Tests\TestCase::class);

test('index data sources include deterministic id ordering', function (string $class, string $method, int $expectedCount = 1) {
    $reflection = new ReflectionMethod($class, $method);
    $fileName = $reflection->getFileName();
    assert(is_string($fileName));

    $lines = file($fileName);
    assert(is_array($lines));

    $source = implode('', array_slice(
        $lines,
        $reflection->getStartLine() - 1,
        $reflection->getEndLine() - $reflection->getStartLine() + 1,
    ));

    preg_match_all('/->(?:orderBy|orderByDesc|latest)\(\s*[\'\"](?:[^\'\"]+\.)?id[\'\"]/', $source, $matches);

    expect($matches[0])->toHaveCount($expectedCount);
})->with([
    [ActivityLogRepository::class, 'paginated'],
    [BranchRepository::class, 'allWithRelations'],
    [BranchRepository::class, 'allOrderedByName'],
    [CashbookRepository::class, 'buildEntriesQuery'],
    [CashCountRepository::class, 'paginatedForCashbook'],
    [CostCodeRepository::class, 'allWithDepartment'],
    [CurrencyDenominationRepository::class, 'allForCurrency'],
    [CurrencyRepository::class, 'allOrderedByShortName'],
    [DepartmentRepository::class, 'allOrderedByName'],
    [LandlordTenantAccessControlService::class, 'listRoles'],
    [LandlordTenantAccessControlService::class, 'listUsersPaginated'],
    [LevelRepository::class, 'allOrderedByPosition'],
    [PaymentRequestRepository::class, 'paginated'],
    [PaymentRequestRepository::class, 'pendingDisbursement'],
    [PositionRepository::class, 'allOrderedByName'],
    [RetirementRequestRepository::class, 'paginated'],
    [RoleRepository::class, 'allWithCounts'],
    [RoleRepository::class, 'allOrderedByName'],
    [StaffRepository::class, 'allWithRelations'],
    [TenantsController::class, 'index'],
    [UserRepository::class, 'allWithRoles'],
    [WorkflowDocumentRequestService::class, 'actionableReferrals'],
    [WorkflowInstanceRepository::class, 'activeStagesForUser'],
    [WorkflowTemplateRepository::class, 'allWithStageCount'],
    [CashBalanceThresholdController::class, 'index', 3],
]);
