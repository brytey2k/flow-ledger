<?php

declare(strict_types=1);

use App\Enums\Tenant\PermissionKey;
use App\Http\Controllers\Web\Tenant\ActivityLogController;
use App\Http\Controllers\Web\Tenant\AttachmentsController;
use App\Http\Controllers\Web\Tenant\Auth\ForgotPasswordController;
use App\Http\Controllers\Web\Tenant\Auth\LoginController;
use App\Http\Controllers\Web\Tenant\Auth\ResetPasswordController;
use App\Http\Controllers\Web\Tenant\BranchesController;
use App\Http\Controllers\Web\Tenant\CashBalanceThresholdController;
use App\Http\Controllers\Web\Tenant\CashbookController;
use App\Http\Controllers\Web\Tenant\CashCountController;
use App\Http\Controllers\Web\Tenant\CommentsController;
use App\Http\Controllers\Web\Tenant\CostCodesController;
use App\Http\Controllers\Web\Tenant\CurrenciesController;
use App\Http\Controllers\Web\Tenant\CurrencyDenominationsController;
use App\Http\Controllers\Web\Tenant\DashboardController;
use App\Http\Controllers\Web\Tenant\DepartmentsController;
use App\Http\Controllers\Web\Tenant\DisbursementsController;
use App\Http\Controllers\Web\Tenant\DocumentationController;
use App\Http\Controllers\Web\Tenant\LevelController;
use App\Http\Controllers\Web\Tenant\LocaleController;
use App\Http\Controllers\Web\Tenant\PaymentRequestAttachmentsController;
use App\Http\Controllers\Web\Tenant\PaymentRequestCancelController;
use App\Http\Controllers\Web\Tenant\PaymentRequestDeclineController;
use App\Http\Controllers\Web\Tenant\PaymentRequestResubmitController;
use App\Http\Controllers\Web\Tenant\PaymentRequestsController;
use App\Http\Controllers\Web\Tenant\PaymentRequestSubmitController;
use App\Http\Controllers\Web\Tenant\PositionsController;
use App\Http\Controllers\Web\Tenant\ReportsController;
use App\Http\Controllers\Web\Tenant\RetirementRequestCancelController;
use App\Http\Controllers\Web\Tenant\RetirementRequestResubmitController;
use App\Http\Controllers\Web\Tenant\RetirementRequestsController;
use App\Http\Controllers\Web\Tenant\RetirementRequestSubmitController;
use App\Http\Controllers\Web\Tenant\RetirementSettlementController;
use App\Http\Controllers\Web\Tenant\RolesController;
use App\Http\Controllers\Web\Tenant\SettingsController;
use App\Http\Controllers\Web\Tenant\StaffController;
use App\Http\Controllers\Web\Tenant\StaffImportController;
use App\Http\Controllers\Web\Tenant\UsersController;
use App\Http\Controllers\Web\Tenant\WorkflowApprovalsController;
use App\Http\Controllers\Web\Tenant\WorkflowParallelGroupsController;
use App\Http\Controllers\Web\Tenant\WorkflowStagesController;
use App\Http\Controllers\Web\Tenant\WorkflowTemplatesController;
use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

Route::middleware([
    'web',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
    'tenant.active',
])->group(function (): void {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('do-login');
    Route::get('/forgot-password', [ForgotPasswordController::class, 'showForm'])->name('password.request');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'sendLink'])->name('password.email');
    Route::get('/reset-password/{token}', [ResetPasswordController::class, 'showForm'])->name('password.reset');
    Route::post('/reset-password', [ResetPasswordController::class, 'reset'])->name('password.update');

    Route::post('/locale', [LocaleController::class, 'update'])->name('locale.update');

    Route::middleware(['auth'])->group(function (): void {
        Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/documentation', [DocumentationController::class, 'index'])->name('documentation');

        // Reports
        Route::get('/reports', [ReportsController::class, 'index'])
            ->can(PermissionKey::AccessReports->value)
            ->name('reports.index');
        Route::get('/reports/expenditure-summary', [ReportsController::class, 'expenditureSummary'])
            ->can(PermissionKey::AccessReports->value)
            ->name('reports.expenditure-summary');
        Route::get('/reports/outstanding-advances', [ReportsController::class, 'outstandingAdvances'])
            ->can(PermissionKey::AccessReports->value)
            ->name('reports.outstanding-advances');
        Route::get('/reports/cash-position', [ReportsController::class, 'cashPosition'])
            ->can(PermissionKey::AccessReports->value)
            ->name('reports.cash-position');
        Route::get('/reports/disbursement-register', [ReportsController::class, 'disbursementRegister'])
            ->can(PermissionKey::AccessReports->value)
            ->name('reports.disbursement-register');
        Route::get('/reports/approval-turnaround', [ReportsController::class, 'approvalTurnaround'])
            ->can(PermissionKey::AccessReports->value)
            ->name('reports.approval-turnaround');
        Route::get('/reports/pending-requests-aging', [ReportsController::class, 'pendingRequestsAging'])
            ->can(PermissionKey::AccessReports->value)
            ->name('reports.pending-requests-aging');
        Route::get('/reports/send-back-rate', [ReportsController::class, 'sendBackRate'])
            ->can(PermissionKey::AccessReports->value)
            ->name('reports.send-back-rate');
        Route::get('/reports/audit-trail', [ReportsController::class, 'auditTrail'])
            ->can(PermissionKey::AccessReports->value)
            ->name('reports.audit-trail');
        Route::get('/reports/requests-by-status', [ReportsController::class, 'requestsByStatus'])
            ->can(PermissionKey::AccessReports->value)
            ->name('reports.requests-by-status');
        Route::get('/reports/workflow-sla', [ReportsController::class, 'workflowSla'])
            ->can(PermissionKey::AccessReports->value)
            ->name('reports.workflow-sla');
        Route::get('/reports/spend-trend', [ReportsController::class, 'spendTrend'])
            ->can(PermissionKey::AccessReports->value)
            ->name('reports.spend-trend');
        Route::get('/reports/top-spenders', [ReportsController::class, 'topSpenders'])
            ->can(PermissionKey::AccessReports->value)
            ->name('reports.top-spenders');

        // Levels
        Route::get('/levels', [LevelController::class, 'index'])
            ->can(PermissionKey::AccessLevels->value)
            ->name('levels.index');
        Route::get('/levels/create', [LevelController::class, 'create'])
            ->can(PermissionKey::CreateLevel->value)
            ->name('levels.create');
        Route::post('/levels', [LevelController::class, 'store'])
            ->can(PermissionKey::CreateLevel->value)
            ->name('levels.store');
        Route::get('/levels/{level}/edit', [LevelController::class, 'edit'])
            ->can(PermissionKey::AccessLevels->value)
            ->name('levels.edit');
        Route::put('/levels/{level}', [LevelController::class, 'update'])
            ->can(PermissionKey::AccessLevels->value)
            ->name('levels.update');
        Route::delete('/levels/{level}', [LevelController::class, 'destroy'])
            ->can(PermissionKey::AccessLevels->value)
            ->name('levels.destroy');

        // Departments
        Route::get('/departments', [DepartmentsController::class, 'index'])
            ->can(PermissionKey::AccessDepartments->value)
            ->name('departments.index');
        Route::get('/departments/create', [DepartmentsController::class, 'create'])
            ->can(PermissionKey::CreateDepartment->value)
            ->name('departments.create');
        Route::get('/departments/import', [DepartmentsController::class, 'importForm'])
            ->can(PermissionKey::CreateDepartment->value)
            ->name('departments.import');
        Route::get('/departments/import/template', [DepartmentsController::class, 'downloadImportTemplate'])
            ->can(PermissionKey::CreateDepartment->value)
            ->name('departments.import.template');
        Route::post('/departments/import', [DepartmentsController::class, 'import'])
            ->can(PermissionKey::CreateDepartment->value)
            ->name('departments.import.store');
        Route::post('/departments', [DepartmentsController::class, 'store'])
            ->can(PermissionKey::CreateDepartment->value)
            ->name('departments.store');
        Route::get('/departments/{department}/edit', [DepartmentsController::class, 'edit'])
            ->can(PermissionKey::AccessDepartments->value)
            ->name('departments.edit');
        Route::put('/departments/{department}', [DepartmentsController::class, 'update'])
            ->can(PermissionKey::AccessDepartments->value)
            ->name('departments.update');
        Route::delete('/departments/{department}', [DepartmentsController::class, 'destroy'])
            ->can(PermissionKey::DeleteDepartment->value)
            ->name('departments.destroy');

        // Cost Codes
        Route::get('/cost-codes', [CostCodesController::class, 'index'])
            ->can(PermissionKey::AccessCostCodes->value)
            ->name('cost-codes.index');
        Route::get('/cost-codes/create', [CostCodesController::class, 'create'])
            ->can(PermissionKey::CreateCostCode->value)
            ->name('cost-codes.create');
        Route::get('/cost-codes/import', [CostCodesController::class, 'importForm'])
            ->can(PermissionKey::CreateCostCode->value)
            ->name('cost-codes.import');
        Route::get('/cost-codes/import/template', [CostCodesController::class, 'downloadImportTemplate'])
            ->can(PermissionKey::CreateCostCode->value)
            ->name('cost-codes.import.template');
        Route::post('/cost-codes', [CostCodesController::class, 'store'])
            ->can(PermissionKey::CreateCostCode->value)
            ->name('cost-codes.store');
        Route::post('/cost-codes/import', [CostCodesController::class, 'import'])
            ->can(PermissionKey::CreateCostCode->value)
            ->name('cost-codes.import.store');
        Route::get('/cost-codes/{cost_code}/edit', [CostCodesController::class, 'edit'])
            ->can(PermissionKey::AccessCostCodes->value)
            ->name('cost-codes.edit');
        Route::put('/cost-codes/{cost_code}', [CostCodesController::class, 'update'])
            ->can(PermissionKey::AccessCostCodes->value)
            ->name('cost-codes.update');
        Route::delete('/cost-codes/{cost_code}', [CostCodesController::class, 'destroy'])
            ->can(PermissionKey::DeleteCostCode->value)
            ->name('cost-codes.destroy');

        // Positions
        Route::get('/positions', [PositionsController::class, 'index'])
            ->can(PermissionKey::AccessPositions->value)
            ->name('positions.index');
        Route::get('/positions/create', [PositionsController::class, 'create'])
            ->can(PermissionKey::CreatePosition->value)
            ->name('positions.create');
        Route::get('/positions/import', [PositionsController::class, 'importForm'])
            ->can(PermissionKey::CreatePosition->value)
            ->name('positions.import');
        Route::get('/positions/import/template', [PositionsController::class, 'downloadImportTemplate'])
            ->can(PermissionKey::CreatePosition->value)
            ->name('positions.import.template');
        Route::post('/positions/import', [PositionsController::class, 'import'])
            ->can(PermissionKey::CreatePosition->value)
            ->name('positions.import.store');
        Route::post('/positions', [PositionsController::class, 'store'])
            ->can(PermissionKey::CreatePosition->value)
            ->name('positions.store');
        Route::get('/positions/{position}/edit', [PositionsController::class, 'edit'])
            ->can(PermissionKey::AccessPositions->value)
            ->name('positions.edit');
        Route::put('/positions/{position}', [PositionsController::class, 'update'])
            ->can(PermissionKey::AccessPositions->value)
            ->name('positions.update');
        Route::delete('/positions/{position}', [PositionsController::class, 'destroy'])
            ->can(PermissionKey::DeletePosition->value)
            ->name('positions.destroy');

        // Staff
        Route::get('/staff', [StaffController::class, 'index'])
            ->can(PermissionKey::AccessStaff->value)
            ->name('staff.index');
        Route::get('/staff/create', [StaffController::class, 'create'])
            ->can(PermissionKey::CreateStaff->value)
            ->name('staff.create');
        Route::get('/staff/import', [StaffImportController::class, 'importForm'])
            ->can(PermissionKey::CreateStaff->value)
            ->name('staff.import');
        Route::get('/staff/import/template', [StaffImportController::class, 'downloadImportTemplate'])
            ->can(PermissionKey::CreateStaff->value)
            ->name('staff.import.template');
        Route::post('/staff/import', [StaffImportController::class, 'import'])
            ->can(PermissionKey::CreateStaff->value)
            ->name('staff.import.store');
        Route::post('/staff', [StaffController::class, 'store'])
            ->can(PermissionKey::CreateStaff->value)
            ->name('staff.store');
        Route::get('/staff/{staff}', [StaffController::class, 'show'])
            ->can(PermissionKey::AccessStaff->value)
            ->name('staff.show');
        Route::get('/staff/{staff}/edit', [StaffController::class, 'edit'])
            ->can(PermissionKey::AccessStaff->value)
            ->name('staff.edit');
        Route::put('/staff/{staff}', [StaffController::class, 'update'])
            ->can(PermissionKey::AccessStaff->value)
            ->name('staff.update');
        Route::delete('/staff/{staff}', [StaffController::class, 'destroy'])
            ->can(PermissionKey::DeleteStaff->value)
            ->name('staff.destroy');

        // Branches
        Route::get('/branches', [BranchesController::class, 'index'])
            ->can(PermissionKey::AccessBranches->value)
            ->name('branches.index');
        Route::get('/branches/create', [BranchesController::class, 'create'])
            ->can(PermissionKey::CreateBranch->value)
            ->name('branches.create');
        Route::post('/branches', [BranchesController::class, 'store'])
            ->can(PermissionKey::CreateBranch->value)
            ->name('branches.store');
        Route::get('/branches/{branch}/edit', [BranchesController::class, 'edit'])
            ->can(PermissionKey::AccessBranches->value)
            ->name('branches.edit');
        Route::put('/branches/{branch}', [BranchesController::class, 'update'])
            ->can(PermissionKey::AccessBranches->value)
            ->name('branches.update');
        Route::delete('/branches/{branch}', [BranchesController::class, 'destroy'])
            ->can(PermissionKey::AccessBranches->value)
            ->name('branches.destroy');

        // Users
        Route::get('/users', [UsersController::class, 'index'])
            ->can(PermissionKey::AccessUsers->value)
            ->name('users.index');
        Route::get('/users/create', [UsersController::class, 'create'])
            ->can(PermissionKey::CreateUser->value)
            ->name('users.create');
        Route::post('/users', [UsersController::class, 'store'])
            ->can(PermissionKey::CreateUser->value)
            ->name('users.store');
        Route::get('/users/{user}', [UsersController::class, 'show'])
            ->can(PermissionKey::AccessUsers->value)
            ->name('users.show');
        Route::get('/users/{user}/edit', [UsersController::class, 'edit'])
            ->can(PermissionKey::AccessUsers->value)
            ->name('users.edit');
        Route::put('/users/{user}', [UsersController::class, 'update'])
            ->can(PermissionKey::AccessUsers->value)
            ->name('users.update');
        Route::delete('/users/{user}', [UsersController::class, 'destroy'])
            ->can(PermissionKey::DeleteUser->value)
            ->name('users.destroy');
        // Activity Log
        Route::get('/activity-log', [ActivityLogController::class, 'index'])
            ->can(PermissionKey::AccessActivityLog->value)
            ->name('activity-log.index');

        Route::get('/users/{user}/permissions', [UsersController::class, 'editPermissions'])
            ->can(PermissionKey::AccessUsers->value)
            ->name('users.permissions.edit');
        Route::put('/users/{user}/permissions', [UsersController::class, 'updatePermissions'])
            ->can(PermissionKey::AccessUsers->value)
            ->name('users.permissions.update');

        // Roles
        Route::get('/roles', [RolesController::class, 'index'])
            ->can(PermissionKey::AccessRoles->value)
            ->name('roles.index');
        Route::get('/roles/create', [RolesController::class, 'create'])
            ->can(PermissionKey::CreateRole->value)
            ->name('roles.create');
        Route::post('/roles', [RolesController::class, 'store'])
            ->can(PermissionKey::CreateRole->value)
            ->name('roles.store');
        Route::get('/roles/{role}/edit', [RolesController::class, 'edit'])
            ->can(PermissionKey::AccessRoles->value)
            ->name('roles.edit');
        Route::put('/roles/{role}', [RolesController::class, 'update'])
            ->can(PermissionKey::AccessRoles->value)
            ->name('roles.update');
        Route::delete('/roles/{role}', [RolesController::class, 'destroy'])
            ->can(PermissionKey::DeleteRole->value)
            ->name('roles.destroy');
        Route::get('/roles/{role}/permissions', [RolesController::class, 'editPermissions'])
            ->can(PermissionKey::AccessRoles->value)
            ->name('roles.permissions.edit');
        Route::put('/roles/{role}/permissions', [RolesController::class, 'updatePermissions'])
            ->can(PermissionKey::AccessRoles->value)
            ->name('roles.permissions.update');

        // Workflow Templates
        Route::get('/workflow-templates', [WorkflowTemplatesController::class, 'index'])
            ->can(PermissionKey::AccessWorkflowTemplates->value)
            ->name('workflow-templates.index');
        Route::get('/workflow-templates/create', [WorkflowTemplatesController::class, 'create'])
            ->can(PermissionKey::CreateWorkflowTemplate->value)
            ->name('workflow-templates.create');
        Route::post('/workflow-templates', [WorkflowTemplatesController::class, 'store'])
            ->can(PermissionKey::CreateWorkflowTemplate->value)
            ->name('workflow-templates.store');
        Route::get('/workflow-templates/{workflowTemplate}', [WorkflowTemplatesController::class, 'show'])
            ->can(PermissionKey::AccessWorkflowTemplates->value)
            ->name('workflow-templates.show');
        Route::get('/workflow-templates/{workflowTemplate}/edit', [WorkflowTemplatesController::class, 'edit'])
            ->can(PermissionKey::EditWorkflowTemplate->value)
            ->name('workflow-templates.edit');
        Route::put('/workflow-templates/{workflowTemplate}', [WorkflowTemplatesController::class, 'update'])
            ->can(PermissionKey::EditWorkflowTemplate->value)
            ->name('workflow-templates.update');
        Route::delete('/workflow-templates/{workflowTemplate}', [WorkflowTemplatesController::class, 'destroy'])
            ->can(PermissionKey::DeleteWorkflowTemplate->value)
            ->name('workflow-templates.destroy');

        // Workflow Stages (nested under templates)
        Route::get('/workflow-templates/{workflowTemplate}/stages/create', [WorkflowStagesController::class, 'create'])
            ->can(PermissionKey::EditWorkflowTemplate->value)
            ->name('workflow-templates.stages.create');
        Route::post('/workflow-templates/{workflowTemplate}/stages', [WorkflowStagesController::class, 'store'])
            ->can(PermissionKey::EditWorkflowTemplate->value)
            ->name('workflow-templates.stages.store');
        Route::get('/workflow-templates/{workflowTemplate}/stages/{workflowStage}/edit', [WorkflowStagesController::class, 'edit'])
            ->can(PermissionKey::EditWorkflowTemplate->value)
            ->name('workflow-templates.stages.edit');
        Route::put('/workflow-templates/{workflowTemplate}/stages/{workflowStage}', [WorkflowStagesController::class, 'update'])
            ->can(PermissionKey::EditWorkflowTemplate->value)
            ->name('workflow-templates.stages.update');
        Route::delete('/workflow-templates/{workflowTemplate}/stages/{workflowStage}', [WorkflowStagesController::class, 'destroy'])
            ->can(PermissionKey::EditWorkflowTemplate->value)
            ->name('workflow-templates.stages.destroy');

        // Workflow Parallel Groups (nested under templates)
        Route::post('/workflow-templates/{workflowTemplate}/parallel-groups', [WorkflowParallelGroupsController::class, 'store'])
            ->can(PermissionKey::EditWorkflowTemplate->value)
            ->name('workflow-templates.parallel-groups.store');
        Route::delete('/workflow-templates/{workflowTemplate}/parallel-groups/{workflowParallelGroup}', [WorkflowParallelGroupsController::class, 'destroy'])
            ->can(PermissionKey::EditWorkflowTemplate->value)
            ->name('workflow-templates.parallel-groups.destroy');

        // Payment Requests
        Route::get('/requests', [PaymentRequestsController::class, 'index'])
            ->can(PermissionKey::AccessPaymentRequests->value)
            ->name('payment-requests.index');
        Route::get('/requests/create', [PaymentRequestsController::class, 'create'])
            ->can(PermissionKey::CreatePaymentRequest->value)
            ->name('payment-requests.create');
        Route::post('/requests', [PaymentRequestsController::class, 'store'])
            ->can(PermissionKey::CreatePaymentRequest->value)
            ->name('payment-requests.store');
        Route::get('/requests/{paymentRequest}', [PaymentRequestsController::class, 'show'])
            ->can(PermissionKey::AccessPaymentRequests->value)
            ->name('payment-requests.show');
        Route::get('/requests/{paymentRequest}/edit', [PaymentRequestsController::class, 'edit'])
            ->can(PermissionKey::CreatePaymentRequest->value)
            ->name('payment-requests.edit');
        Route::put('/requests/{paymentRequest}', [PaymentRequestsController::class, 'update'])
            ->can(PermissionKey::CreatePaymentRequest->value)
            ->name('payment-requests.update');
        Route::delete('/requests/{paymentRequest}', [PaymentRequestsController::class, 'destroy'])
            ->can(PermissionKey::DeletePaymentRequest->value)
            ->name('payment-requests.destroy');
        Route::post('/requests/{paymentRequest}/submit', [PaymentRequestSubmitController::class, 'store'])
            ->can(PermissionKey::CreatePaymentRequest->value)
            ->name('payment-requests.submit');
        Route::post('/requests/{paymentRequest}/resubmit', [PaymentRequestResubmitController::class, 'store'])
            ->can(PermissionKey::CreatePaymentRequest->value)
            ->name('payment-requests.resubmit');
        Route::post('/requests/{paymentRequest}/cancel', [PaymentRequestCancelController::class, 'store'])
            ->can(PermissionKey::AccessPaymentRequests->value)
            ->name('payment-requests.cancel');
        Route::post('/requests/{paymentRequest}/decline', [PaymentRequestDeclineController::class, 'store'])
            ->can(PermissionKey::AccessPaymentRequests->value)
            ->name('payment-requests.decline');
        Route::post('/requests/{paymentRequest}/attachments', [PaymentRequestAttachmentsController::class, 'store'])
            ->can(PermissionKey::CreatePaymentRequest->value)
            ->name('payment-requests.attachments.store');
        Route::post('/requests/{paymentRequest}/comments', [CommentsController::class, 'store'])
            ->can(PermissionKey::AccessPaymentRequests->value)
            ->name('payment-requests.comments.store');
        Route::delete('/requests/{paymentRequest}/comments/{comment}', [CommentsController::class, 'destroy'])
            ->can(PermissionKey::AccessPaymentRequests->value)
            ->name('payment-requests.comments.destroy');

        // Retirement Requests
        Route::get('/retirements', [RetirementRequestsController::class, 'index'])
            ->can(PermissionKey::AccessRetirementRequests->value)
            ->name('retirement-requests.index');
        Route::get('/requests/{paymentRequest}/retirement/create', [RetirementRequestsController::class, 'create'])
            ->can(PermissionKey::CreateRetirementRequest->value)
            ->name('retirement-requests.create');
        Route::post('/requests/{paymentRequest}/retirement', [RetirementRequestsController::class, 'store'])
            ->can(PermissionKey::CreateRetirementRequest->value)
            ->name('retirement-requests.store');
        Route::get('/retirements/{retirementRequest}', [RetirementRequestsController::class, 'show'])
            ->can(PermissionKey::AccessRetirementRequests->value)
            ->name('retirement-requests.show');
        Route::get('/retirements/{retirementRequest}/edit', [RetirementRequestsController::class, 'edit'])
            ->can(PermissionKey::CreateRetirementRequest->value)
            ->name('retirement-requests.edit');
        Route::put('/retirements/{retirementRequest}', [RetirementRequestsController::class, 'update'])
            ->can(PermissionKey::CreateRetirementRequest->value)
            ->name('retirement-requests.update');
        Route::post('/retirements/{retirementRequest}/submit', [RetirementRequestSubmitController::class, 'store'])
            ->can(PermissionKey::CreateRetirementRequest->value)
            ->name('retirement-requests.submit');
        Route::post('/retirements/{retirementRequest}/resubmit', [RetirementRequestResubmitController::class, 'store'])
            ->can(PermissionKey::CreateRetirementRequest->value)
            ->name('retirement-requests.resubmit');
        Route::post('/retirements/{retirementRequest}/cancel', [RetirementRequestCancelController::class, 'store'])
            ->can(PermissionKey::AccessRetirementRequests->value)
            ->name('retirement-requests.cancel');
        Route::post('/retirements/{retirementRequest}/settle', [RetirementSettlementController::class, 'store'])
            ->can(PermissionKey::SettleRetirements->value)
            ->name('retirement-requests.settle');
        Route::post('/retirements/{retirementRequest}/attachments', [AttachmentsController::class, 'store'])
            ->can(PermissionKey::CreateRetirementRequest->value)
            ->name('retirement-requests.attachments.store');
        Route::get('/attachments/{attachment}/download', [AttachmentsController::class, 'download'])
            ->name('attachments.download');
        Route::delete('/attachments/{attachment}', [AttachmentsController::class, 'destroy'])
            ->name('attachments.destroy');

        // Disbursements
        Route::get('/disbursements', [DisbursementsController::class, 'index'])
            ->can(PermissionKey::DisburseRequests->value)
            ->name('disbursements.index');
        Route::post('/requests/{paymentRequest}/disburse', [DisbursementsController::class, 'store'])
            ->can(PermissionKey::DisburseRequests->value)
            ->name('disbursements.store');

        // Approvals
        Route::get('/approvals', [WorkflowApprovalsController::class, 'index'])
            ->can(PermissionKey::ApproveRequests->value)
            ->name('approvals.index');
        Route::get('/approvals/{instanceStage}', [WorkflowApprovalsController::class, 'show'])
            ->can(PermissionKey::ApproveRequests->value)
            ->name('approvals.show');
        Route::post('/approvals/{instanceStage}', [WorkflowApprovalsController::class, 'store'])
            ->can(PermissionKey::ApproveRequests->value)
            ->name('approvals.store');

        // Currencies
        Route::get('/currencies', [CurrenciesController::class, 'index'])
            ->can(PermissionKey::AccessCurrencies->value)
            ->name('currencies.index');
        Route::get('/currencies/create', [CurrenciesController::class, 'create'])
            ->can(PermissionKey::CreateCurrency->value)
            ->name('currencies.create');
        Route::post('/currencies', [CurrenciesController::class, 'store'])
            ->can(PermissionKey::CreateCurrency->value)
            ->name('currencies.store');
        Route::get('/currencies/{currency}/edit', [CurrenciesController::class, 'edit'])
            ->can(PermissionKey::AccessCurrencies->value)
            ->name('currencies.edit');
        Route::put('/currencies/{currency}', [CurrenciesController::class, 'update'])
            ->can(PermissionKey::AccessCurrencies->value)
            ->name('currencies.update');
        Route::delete('/currencies/{currency}', [CurrenciesController::class, 'destroy'])
            ->can(PermissionKey::DeleteCurrency->value)
            ->name('currencies.destroy');

        // Cashbook
        Route::get('/cashbook', [CashbookController::class, 'branches'])
            ->can(PermissionKey::AccessCashbook->value)
            ->name('cashbook.branches');
        Route::get('/branches/{branch}/cashbook', [CashbookController::class, 'index'])
            ->can(PermissionKey::AccessCashbook->value)
            ->name('cashbook.index');
        Route::get('/branches/{branch}/cashbook/receipts/create', [CashbookController::class, 'create'])
            ->can(PermissionKey::CreateCashbookEntry->value)
            ->name('cashbook.create');
        Route::post('/branches/{branch}/cashbook/receipts', [CashbookController::class, 'store'])
            ->can(PermissionKey::CreateCashbookEntry->value)
            ->name('cashbook.store');
        Route::get('/branches/{branch}/cashbook/export', [CashbookController::class, 'export'])
            ->can(PermissionKey::AccessCashbook->value)
            ->name('cashbook.export');
        Route::delete('/branches/{branch}/cashbook/entries/{entry}', [CashbookController::class, 'destroy'])
            ->can(PermissionKey::DeleteCashbookEntry->value)
            ->name('cashbook.destroy');

        // Currency Denominations
        Route::get('/currencies/{currency}/denominations', [CurrencyDenominationsController::class, 'index'])
            ->can(PermissionKey::ManageCurrencyDenominations->value)
            ->name('currency.denominations.index');
        Route::get('/currencies/{currency}/denominations/create', [CurrencyDenominationsController::class, 'create'])
            ->can(PermissionKey::ManageCurrencyDenominations->value)
            ->name('currency.denominations.create');
        Route::post('/currencies/{currency}/denominations', [CurrencyDenominationsController::class, 'store'])
            ->can(PermissionKey::ManageCurrencyDenominations->value)
            ->name('currency.denominations.store');
        Route::get('/currencies/{currency}/denominations/{denomination}/edit', [CurrencyDenominationsController::class, 'edit'])
            ->can(PermissionKey::ManageCurrencyDenominations->value)
            ->name('currency.denominations.edit');
        Route::put('/currencies/{currency}/denominations/{denomination}', [CurrencyDenominationsController::class, 'update'])
            ->can(PermissionKey::ManageCurrencyDenominations->value)
            ->name('currency.denominations.update');
        Route::delete('/currencies/{currency}/denominations/{denomination}', [CurrencyDenominationsController::class, 'destroy'])
            ->can(PermissionKey::ManageCurrencyDenominations->value)
            ->name('currency.denominations.destroy');

        // Cash Count
        Route::get('/branches/{branch}/cash-count', [CashCountController::class, 'index'])
            ->can(PermissionKey::AccessCashCount->value)
            ->name('cash-count.index');
        Route::get('/branches/{branch}/cash-count/create', [CashCountController::class, 'create'])
            ->can(PermissionKey::CreateCashCount->value)
            ->name('cash-count.create');
        Route::post('/branches/{branch}/cash-count', [CashCountController::class, 'store'])
            ->can(PermissionKey::CreateCashCount->value)
            ->name('cash-count.store');
        Route::get('/branches/{branch}/cash-count/{cashCount}', [CashCountController::class, 'show'])
            ->can(PermissionKey::AccessCashCount->value)
            ->name('cash-count.show');
        Route::delete('/branches/{branch}/cash-count/{cashCount}', [CashCountController::class, 'destroy'])
            ->can(PermissionKey::DeleteCashCount->value)
            ->name('cash-count.destroy');

        // Cash Balance Thresholds
        Route::get('/cash-balance-thresholds', [CashBalanceThresholdController::class, 'index'])
            ->can(PermissionKey::AccessSettings->value)
            ->name('cash-balance-thresholds.index');
        Route::post('/cash-balance-thresholds', [CashBalanceThresholdController::class, 'store'])
            ->can(PermissionKey::AccessSettings->value)
            ->name('cash-balance-thresholds.store');
        Route::put('/cash-balance-thresholds/{threshold}', [CashBalanceThresholdController::class, 'update'])
            ->can(PermissionKey::AccessSettings->value)
            ->name('cash-balance-thresholds.update');
        Route::delete('/cash-balance-thresholds/{threshold}', [CashBalanceThresholdController::class, 'destroy'])
            ->can(PermissionKey::AccessSettings->value)
            ->name('cash-balance-thresholds.destroy');

        Route::get('/settings', [SettingsController::class, 'index'])
            ->can(PermissionKey::AccessSettings->value)
            ->name('settings.index');
        Route::put('/settings', [SettingsController::class, 'update'])
            ->can(PermissionKey::AccessSettings->value)
            ->name('settings.update');
    });
});
