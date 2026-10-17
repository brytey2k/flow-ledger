<?php

declare(strict_types=1);

uses(Tests\TenantAppTestCase::class);
use App\DTOs\Landlord\TenantDataToolFilters;
use App\Enums\AuditLogStatus;
use App\Enums\TenantDataTool;
use App\Jobs\RunTenantDataToolJob;
use App\Models\Landlord\AuditLog;
use App\Models\Tenant\Attachment;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Cashbook;
use App\Models\Tenant\CashbookEntry;
use App\Models\Tenant\Currency;
use App\Models\Tenant\PaymentRequest;
use App\Models\Tenant\RetirementRequest;
use App\Models\Tenant\Staff;
use App\Models\Tenant\WorkflowTemplate;
use App\Services\TenantDataToolService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

function dataToolStaff(): Staff
{
    return Staff::factory()->create(['branch_id' => test()->branch->id]);
}

function dataToolCashbook(string $balance = '0'): Cashbook
{
    return Cashbook::create([
        'branch_id' => Branch::factory()->create()->id,
        'currency_id' => Currency::factory()->create()->id,
        'balance' => $balance,
    ]);
}

function dataToolPaymentRequest(string $status, Cashbook|null $cashbook = null): PaymentRequest
{
    $request = PaymentRequest::factory()->advance()->create([
        'staff_id' => dataToolStaff()->id,
        'branch_id' => test()->branch->id,
        'status' => $status,
    ]);

    if ($cashbook !== null) {
        CashbookEntry::create([
            'cashbook_id' => $cashbook->id,
            'type' => 'credit',
            'amount' => 100,
            'description' => 'Payment disbursed',
            'entry_date' => today(),
            'sourceable_type' => PaymentRequest::class,
            'sourceable_id' => $request->id,
        ]);
    }

    return $request;
}

function dataToolService(): TenantDataToolService
{
    return app(TenantDataToolService::class);
}

test('clearing retirements reverts payment request, removes settlement entry and restores balance', function () {
    Storage::fake('local');
    $cashbook = dataToolCashbook('500.00');
    $payment = dataToolPaymentRequest('retired');
    $retirement = RetirementRequest::factory()->create(['payment_request_id' => $payment->id, 'status' => 'settled']);
    CashbookEntry::create([
        'cashbook_id' => $cashbook->id,
        'type' => 'debit',
        'amount' => 40,
        'description' => 'Retirement settlement',
        'entry_date' => today(),
        'sourceable_type' => RetirementRequest::class,
        'sourceable_id' => $retirement->id,
    ]);
    Storage::disk('local')->put('retirements/x/receipt.pdf', 'pdf');
    Attachment::factory()->create([
        'attachable_type' => RetirementRequest::class,
        'attachable_id' => $retirement->id,
        'user_id' => $this->user->id,
        'path' => 'retirements/x/receipt.pdf',
    ]);

    $preview = dataToolService()->preview($this->tenant, TenantDataTool::ClearRetirements, new TenantDataToolFilters());

    expect($preview)->toMatchArray(['retirement_requests' => 1, 'cashbook_entries' => 1, 'attachments' => 1, 'files' => 1, 'payment_requests_reverted_to_disbursed' => 1]);
    expect(RetirementRequest::count())->toBe(1);
    expect($payment->fresh()->status)->toBe('retired');
    Storage::disk('local')->assertExists('retirements/x/receipt.pdf');

    $counts = dataToolService()->execute($this->tenant, TenantDataTool::ClearRetirements, new TenantDataToolFilters());

    expect($counts)->toEqual($preview);
    expect(RetirementRequest::withTrashed()->count())->toBe(0);
    expect(CashbookEntry::count())->toBe(0);
    expect(Attachment::count())->toBe(0);
    expect($payment->fresh()->status)->toBe('disbursed');
    expect((float) $cashbook->fresh()->balance)->toBe(460.0);
    Storage::disk('local')->assertMissing('retirements/x/receipt.pdf');
});

test('clearing payment requests with keep drafts removes the rest along with retirements and entries', function () {
    $cashbook = dataToolCashbook('900.00');
    $draft = dataToolPaymentRequest('draft');
    $disbursed = dataToolPaymentRequest('disbursed', $cashbook);
    RetirementRequest::factory()->create(['payment_request_id' => $disbursed->id, 'status' => 'draft']);

    $counts = dataToolService()->execute($this->tenant, TenantDataTool::ClearPaymentRequests, new TenantDataToolFilters(keepDrafts: true));

    expect($counts)->toMatchArray(['payment_requests' => 1, 'retirement_requests' => 1, 'cashbook_entries' => 1]);
    expect(PaymentRequest::withTrashed()->pluck('id')->all())->toBe([$draft->id]);
    expect(RetirementRequest::withTrashed()->count())->toBe(0);
    expect((float) $cashbook->fresh()->balance)->toBe(1000.0);
});

test('clearing payment requests honours status filter', function () {
    $approved = dataToolPaymentRequest('approved');
    dataToolPaymentRequest('denied');

    dataToolService()->execute($this->tenant, TenantDataTool::ClearPaymentRequests, new TenantDataToolFilters(statuses: ['denied']));

    expect(PaymentRequest::pluck('id')->all())->toBe([$approved->id]);
});

test('resetting in-flight workflows deletes in-progress instances and returns requests to draft', function () {
    $template = WorkflowTemplate::factory()->create();
    $stuck = dataToolPaymentRequest('in_workflow');
    $done = dataToolPaymentRequest('approved');
    $stuckInstance = DB::table('workflow_instances')->insertGetId([
        'workflow_template_id' => $template->id, 'workflowable_type' => PaymentRequest::class,
        'workflowable_id' => $stuck->id, 'status' => 'in_progress', 'created_at' => now(), 'updated_at' => now(),
    ]);
    DB::table('workflow_instances')->insert([
        'workflow_template_id' => $template->id, 'workflowable_type' => PaymentRequest::class,
        'workflowable_id' => $done->id, 'status' => 'completed', 'created_at' => now(), 'updated_at' => now(),
    ]);

    $counts = dataToolService()->execute($this->tenant, TenantDataTool::ResetInFlightWorkflows, new TenantDataToolFilters());

    expect($counts)->toMatchArray(['payment_requests_returned_to_draft' => 1, 'workflow_instances' => 1]);
    expect(DB::table('workflow_instances')->where('id', $stuckInstance)->exists())->toBeFalse();
    expect(DB::table('workflow_instances')->count())->toBe(1);
    expect($stuck->fresh()->status)->toBe('draft');
    expect($done->fresh()->status)->toBe('approved');
});

test('clearing cashbook removes entries and counts in range and adjusts balance', function () {
    $cashbook = dataToolCashbook('70.00');
    foreach ([['debit', 100, '2026-01-10'], ['credit', 30, '2026-01-11'], ['debit', 5, '2026-03-01']] as [$type, $amount, $date]) {
        CashbookEntry::create([
            'cashbook_id' => $cashbook->id, 'type' => $type, 'amount' => $amount,
            'description' => 'x', 'entry_date' => $date,
        ]);
    }

    $counts = dataToolService()->execute($this->tenant, TenantDataTool::ClearCashbook, TenantDataToolFilters::fromArray(['to' => '2026-02-01']));

    expect($counts['cashbook_entries'])->toBe(2);
    expect(CashbookEntry::count())->toBe(1);
    expect((float) $cashbook->fresh()->balance)->toBe(0.0);
});

test('recalculating balances corrects drifted cashbooks only', function () {
    $drifted = dataToolCashbook('999.00');
    CashbookEntry::create(['cashbook_id' => $drifted->id, 'type' => 'debit', 'amount' => 80, 'description' => 'x', 'entry_date' => today()]);
    $correct = dataToolCashbook('0');

    $counts = dataToolService()->execute($this->tenant, TenantDataTool::RecalculateCashbookBalances, new TenantDataToolFilters());

    expect($counts['cashbooks_corrected'])->toBe(1);
    expect((float) $drifted->fresh()->balance)->toBe(80.0);
    expect((float) $correct->fresh()->balance)->toBe(0.0);
});

test('clearing activity log respects date range', function () {
    DB::table('activity_log')->insert([
        ['description' => 'oldest', 'created_at' => now()->subYears(10), 'updated_at' => now()->subYears(10)],
        ['description' => 'old', 'created_at' => now()->subYears(8), 'updated_at' => now()->subYears(8)],
    ]);
    $before = DB::table('activity_log')->count();

    dataToolService()->execute($this->tenant, TenantDataTool::ClearActivityLog, TenantDataToolFilters::fromArray(['to' => now()->subYears(9)->toDateString()]));

    expect(DB::table('activity_log')->count())->toBe($before - 1);
    expect(DB::table('activity_log')->where('description', 'old')->exists())->toBeTrue();
});

test('clearing activity log never removes rows inside the retention period', function () {
    DB::table('activity_log')->insert([
        ['description' => 'expired', 'created_at' => now()->subYears(8), 'updated_at' => now()->subYears(8)],
        ['description' => 'recent', 'created_at' => now()->subYears(2), 'updated_at' => now()->subYears(2)],
        ['description' => 'today', 'created_at' => now(), 'updated_at' => now()],
    ]);

    dataToolService()->execute($this->tenant, TenantDataTool::ClearActivityLog, new TenantDataToolFilters());

    expect(DB::table('activity_log')->where('description', 'expired')->exists())->toBeFalse();
    expect(DB::table('activity_log')->where('description', 'recent')->exists())->toBeTrue();
    expect(DB::table('activity_log')->where('description', 'today')->exists())->toBeTrue();
});

test('clearing payment requests keeps their activity inside the retention period', function () {
    $paymentRequest = PaymentRequest::factory()->create();
    DB::table('activity_log')->insert([
        'description' => 'kept', 'subject_type' => PaymentRequest::class, 'subject_id' => $paymentRequest->id,
        'created_at' => now(), 'updated_at' => now(),
    ]);

    dataToolService()->execute($this->tenant, TenantDataTool::ClearPaymentRequests, new TenantDataToolFilters());

    expect(DB::table('activity_log')->where('description', 'kept')->exists())->toBeTrue();
});

test('job records result and releases the tenant lock', function () {
    dataToolPaymentRequest('denied');
    $log = AuditLog::create([
        'tenant_id' => (string) $this->tenant->getTenantKey(),
        'action' => TenantDataTool::ClearPaymentRequests->value,
        'status' => AuditLogStatus::Queued,
        'filters' => (new TenantDataToolFilters())->toArray(),
    ]);

    (new RunTenantDataToolJob($log->id))->handle(dataToolService());

    $log->refresh();
    expect($log->status)->toBe(AuditLogStatus::Completed);
    expect($log->result['payment_requests'])->toBe(1);
    expect(Cache::lock(RunTenantDataToolJob::lockKey($log->tenant_id))->get())->toBeTrue();
});

test('job fails without running when tenant is locked', function () {
    $log = AuditLog::create([
        'tenant_id' => (string) $this->tenant->getTenantKey(),
        'action' => TenantDataTool::ClearPaymentRequests->value,
        'status' => AuditLogStatus::Queued,
        'filters' => [],
    ]);
    $held = Cache::lock(RunTenantDataToolJob::lockKey($log->tenant_id), 60);
    $held->get();

    (new RunTenantDataToolJob($log->id))->handle(dataToolService());

    expect($log->refresh()->status)->toBe(AuditLogStatus::Failed);
    $held->release();
});
