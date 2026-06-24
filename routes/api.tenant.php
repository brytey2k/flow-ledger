<?php

declare(strict_types=1);

use App\Http\Controllers\Api\Tenant\ApprovalActionController;
use App\Http\Controllers\Api\Tenant\ApprovalController;
use App\Http\Controllers\Api\Tenant\AttachmentController;
use App\Http\Controllers\Api\Tenant\BranchController;
use App\Http\Controllers\Api\Tenant\CashbookController;
use App\Http\Controllers\Api\Tenant\CommentController;
use App\Http\Controllers\Api\Tenant\CostCodeController;
use App\Http\Controllers\Api\Tenant\CurrencyController;
use App\Http\Controllers\Api\Tenant\DashboardController;
use App\Http\Controllers\Api\Tenant\DepartmentController;
use App\Http\Controllers\Api\Tenant\DisbursementController;
use App\Http\Controllers\Api\Tenant\MeController;
use App\Http\Controllers\Api\Tenant\PaymentRequestActionController;
use App\Http\Controllers\Api\Tenant\PaymentRequestController;
use App\Http\Controllers\Api\Tenant\RetirementRequestActionController;
use App\Http\Controllers\Api\Tenant\RetirementRequestController;
use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

Route::prefix('api')
    ->middleware([
        'api',
        InitializeTenancyByDomain::class,
        PreventAccessFromCentralDomains::class,
        'tenant.active',
        'auth:iam_jwt',
    ])
    ->group(function (): void {
        // Profile & Dashboard
        Route::get('/me', MeController::class)->name('api.me');
        Route::get('/dashboard', DashboardController::class)->name('api.dashboard');

        // Payment Requests
        Route::apiResource('payment-requests', PaymentRequestController::class)
            ->names('api.payment-requests')
            ->parameters(['payment-requests' => 'paymentRequest']);
        Route::post('payment-requests/{paymentRequest}/submit', [PaymentRequestActionController::class, 'submit'])->name('api.payment-requests.submit');
        Route::post('payment-requests/{paymentRequest}/cancel', [PaymentRequestActionController::class, 'cancel'])->name('api.payment-requests.cancel');
        Route::post('payment-requests/{paymentRequest}/resubmit', [PaymentRequestActionController::class, 'resubmit'])->name('api.payment-requests.resubmit');

        // Retirement Requests
        Route::apiResource('retirement-requests', RetirementRequestController::class)
            ->only(['index', 'store', 'show', 'update'])
            ->names('api.retirement-requests')
            ->parameters(['retirement-requests' => 'retirementRequest']);
        Route::post('retirement-requests/{retirementRequest}/submit', [RetirementRequestActionController::class, 'submit'])->name('api.retirement-requests.submit');
        Route::post('retirement-requests/{retirementRequest}/cancel', [RetirementRequestActionController::class, 'cancel'])->name('api.retirement-requests.cancel');
        Route::post('retirement-requests/{retirementRequest}/resubmit', [RetirementRequestActionController::class, 'resubmit'])->name('api.retirement-requests.resubmit');

        // Approvals
        Route::get('approvals', [ApprovalController::class, 'index'])->name('api.approvals.index');
        Route::get('approvals/{workflowInstanceStage}', [ApprovalController::class, 'show'])->name('api.approvals.show');
        Route::post('approvals/{workflowInstanceStage}/approve', [ApprovalActionController::class, 'approve'])->name('api.approvals.approve');
        Route::post('approvals/{workflowInstanceStage}/reject', [ApprovalActionController::class, 'reject'])->name('api.approvals.reject');
        Route::post('approvals/{workflowInstanceStage}/send-back', [ApprovalActionController::class, 'sendBack'])->name('api.approvals.send-back');

        // Disbursements
        Route::get('disbursements', [DisbursementController::class, 'index'])->name('api.disbursements.index');
        Route::post('disbursements/{paymentRequest}', [DisbursementController::class, 'store'])->name('api.disbursements.store');

        // Cashbook
        Route::get('cashbook', [CashbookController::class, 'index'])->name('api.cashbook.index');
        Route::post('cashbook/entries', [CashbookController::class, 'storeEntry'])->name('api.cashbook.entries.store');

        // Attachments & Comments
        Route::post('payment-requests/{paymentRequest}/attachments', [AttachmentController::class, 'storeForPaymentRequest'])->name('api.payment-requests.attachments.store');
        Route::post('retirement-requests/{retirementRequest}/attachments', [AttachmentController::class, 'storeForRetirementRequest'])->name('api.retirement-requests.attachments.store');
        Route::delete('attachments/{attachment}', [AttachmentController::class, 'destroy'])->name('api.attachments.destroy');
        Route::post('payment-requests/{paymentRequest}/comments', [CommentController::class, 'storeForPaymentRequest'])->name('api.payment-requests.comments.store');
        Route::post('retirement-requests/{retirementRequest}/comments', [CommentController::class, 'storeForRetirementRequest'])->name('api.retirement-requests.comments.store');

        // Reference Data
        Route::get('branches', BranchController::class)->name('api.branches.index');
        Route::get('currencies', CurrencyController::class)->name('api.currencies.index');
        Route::get('cost-codes', CostCodeController::class)->name('api.cost-codes.index');
        Route::get('departments', DepartmentController::class)->name('api.departments.index');
    });
