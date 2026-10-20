<?php

declare(strict_types=1);

uses(Tests\TenantAppTestCase::class);
use App\DTOs\Landlord\TenantDataToolFilters;
use App\Enums\Tenant\UserStatus;
use App\Enums\TenantDataTool;
use App\Http\Middleware\EnsureTenantIsNotInMaintenance;
use App\Interfaces\SessionInvalidatorInterface;
use App\Jobs\InviteUserToIamJob;
use App\Models\Tenant\Attachment;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Cashbook;
use App\Models\Tenant\CashbookEntry;
use App\Models\Tenant\Currency;
use App\Models\Tenant\PaymentRequest;
use App\Models\Tenant\RetirementRequest;
use App\Models\Tenant\Staff;
use App\Models\Tenant\User;
use App\Models\Tenant\WorkflowStage;
use App\Models\Tenant\WorkflowTemplate;
use App\Services\TenantAnnouncementService;
use App\Services\TenantDataToolService;
use App\Services\TenantFailedJobService;
use App\Services\TenantFixUpService;
use App\Services\TenantHealthService;
use App\Services\TenantStorageService;
use App\Services\TenantUserAdminService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

function opsPaymentRequest(string $status, array $attributes = []): PaymentRequest
{
    return PaymentRequest::factory()->advance()->create([
        'staff_id' => Staff::factory()->create(['branch_id' => test()->branch->id])->id,
        'branch_id' => test()->branch->id,
        'status' => $status,
        ...$attributes,
    ]);
}

function opsCashbook(string $balance): Cashbook
{
    return Cashbook::create(['branch_id' => Branch::factory()->create()->id, 'currency_id' => Currency::factory()->create()->id, 'balance' => $balance]);
}

test('export builds a zip of csv files and keeps it out of the preview', function () {
    Storage::fake('local');
    opsPaymentRequest('draft', ['notes' => '=HYPERLINK("x")']);
    $service = app(TenantDataToolService::class);

    $service->preview($this->tenant, TenantDataTool::ExportData, new TenantDataToolFilters());
    expect($service->artifactPath())->toBeNull();

    $counts = $service->execute($this->tenant, TenantDataTool::ExportData, new TenantDataToolFilters());

    expect($counts['payment_requests'])->toBeGreaterThanOrEqual(1);
    $path = $service->artifactPath();
    expect($path)->toStartWith('tenant-exports/')->and(Storage::disk('local')->exists($path))->toBeTrue();

    $zip = new ZipArchive();
    $zip->open(Storage::disk('local')->path($path));
    $csv = $zip->getFromName('payment_requests.csv');
    expect($csv)->toContain('total_amount')->toContain("'=HYPERLINK")->and($zip->locateName('activity_log.csv'))->not->toBeFalse();
});

test('force cancel overrides an open document window', function () {
    $template = WorkflowTemplate::factory()->create();
    $stage = WorkflowStage::factory()->create(['workflow_template_id' => $template->id]);
    $request = opsPaymentRequest('in_workflow');
    $instanceId = DB::table('workflow_instances')->insertGetId([
        'workflow_template_id' => $template->id, 'workflowable_type' => PaymentRequest::class,
        'workflowable_id' => $request->id, 'status' => 'in_progress', 'created_at' => now(), 'updated_at' => now(),
    ]);
    $instanceStageId = DB::table('workflow_instance_stages')->insertGetId([
        'workflow_instance_id' => $instanceId, 'workflow_stage_id' => $stage->id, 'status' => 'active',
        'created_at' => now(), 'updated_at' => now(),
    ]);
    DB::table('workflow_document_requests')->insert([
        'workflow_instance_id' => $instanceId, 'opening_instance_stage_id' => $instanceStageId,
        'requester_user_id' => $this->user->id, 'opened_by_user_id' => $this->user->id,
        'status' => 'awaiting_uploads', 'reason' => 'receipts', 'created_at' => now(), 'updated_at' => now(),
    ]);

    $result = app(TenantFixUpService::class)->forceCancel($this->tenant, 'payment', $request->id);

    expect($result)->toBe(['requests_cancelled' => 1, 'document_requests_cancelled' => 1]);
    expect($request->fresh()->status)->toBe('cancelled');
    expect(DB::table('workflow_instances')->where('id', $instanceId)->value('status'))->toBe('cancelled');
});

test('force cancel refuses a request that cannot be cancelled', function () {
    $request = opsPaymentRequest('disbursed');

    app(TenantFixUpService::class)->forceCancel($this->tenant, 'payment', $request->id);
})->throws(LogicException::class);

test('reopening a settled retirement restores balance and request status', function () {
    $cashbook = opsCashbook('500.00');
    $payment = opsPaymentRequest('retired');
    $retirement = RetirementRequest::factory()->create([
        'payment_request_id' => $payment->id, 'status' => 'settled', 'settled_at' => now(), 'settlement_notes' => 'ok',
    ]);
    CashbookEntry::create([
        'cashbook_id' => $cashbook->id, 'type' => 'debit', 'amount' => 40, 'description' => 'x',
        'entry_date' => today(), 'sourceable_type' => RetirementRequest::class, 'sourceable_id' => $retirement->id,
    ]);

    $result = app(TenantFixUpService::class)->reopenRetirement($this->tenant, $retirement->id, 'admin@example.com');

    expect($result['retirements_reopened'])->toBe(1);
    expect($retirement->fresh())->status->toBe('approved')->settled_at->toBeNull()->settlement_notes->toBeNull();
    expect($payment->fresh()->status)->toBe('disbursed');
    expect((float) $cashbook->fresh()->balance)->toBe(460.0);
    expect(CashbookEntry::withTrashed()->count())->toBe(0);
});

test('reopening only works on settled retirements', function () {
    $retirement = RetirementRequest::factory()->create(['status' => 'draft']);

    app(TenantFixUpService::class)->reopenRetirement($this->tenant, $retirement->id, 'admin@example.com');
})->throws(LogicException::class);

test('reversing a disbursement restores the cashbook', function () {
    $cashbook = opsCashbook('400.00');
    $request = opsPaymentRequest('disbursed', ['disbursed_at' => now(), 'disbursement_reference' => 'ref']);
    CashbookEntry::create([
        'cashbook_id' => $cashbook->id, 'type' => 'credit', 'amount' => 100, 'description' => 'Payment disbursed',
        'entry_date' => today(), 'sourceable_type' => PaymentRequest::class, 'sourceable_id' => $request->id,
    ]);

    app(TenantFixUpService::class)->reverseDisbursement($this->tenant, $request->id, 'admin@example.com');

    expect($request->fresh())->status->toBe('approved')->disbursed_at->toBeNull()->disbursement_reference->toBeNull();
    expect((float) $cashbook->fresh()->balance)->toBe(500.0);
});

test('reversing a disbursement is refused while a retirement exists', function () {
    $request = opsPaymentRequest('disbursed');
    RetirementRequest::factory()->create(['payment_request_id' => $request->id, 'status' => 'draft']);

    app(TenantFixUpService::class)->reverseDisbursement($this->tenant, $request->id, 'admin@example.com');
})->throws(LogicException::class);

test('health snapshot reports workload and storage', function () {
    opsPaymentRequest('draft');
    Attachment::factory()->create([
        'attachable_type' => PaymentRequest::class, 'attachable_id' => opsPaymentRequest('draft')->id,
        'user_id' => $this->user->id, 'size' => 2048,
    ]);

    $snapshot = app(TenantHealthService::class)->snapshot($this->tenant);

    expect($snapshot['payment_requests']['draft'])->toBeGreaterThanOrEqual(2)
        ->and($snapshot['attachments'])->toBeGreaterThanOrEqual(1)
        ->and($snapshot['storage_bytes'])->toBeGreaterThanOrEqual(2048)
        ->and($snapshot['users'])->not->toBeEmpty();
});

test('storage report finds and removes orphaned files', function () {
    Storage::fake('local');
    Storage::disk('local')->put('payment-requests/1/attachments/kept.pdf', 'a');
    Storage::disk('local')->put('payment-requests/1/attachments/orphan.pdf', 'bb');
    Attachment::factory()->create([
        'attachable_type' => PaymentRequest::class, 'attachable_id' => opsPaymentRequest('draft')->id,
        'user_id' => $this->user->id, 'path' => 'payment-requests/1/attachments/kept.pdf', 'size' => 1,
    ]);
    Attachment::factory()->create([
        'attachable_type' => PaymentRequest::class, 'attachable_id' => opsPaymentRequest('draft')->id,
        'user_id' => $this->user->id, 'path' => 'payment-requests/1/attachments/gone.pdf', 'size' => 1,
    ]);
    $service = app(TenantStorageService::class);

    $report = $service->report($this->tenant);

    expect($report['orphaned_files'])->toBe(['payment-requests/1/attachments/orphan.pdf'])
        ->and($report['orphaned_bytes'])->toBe(2)
        ->and($report['missing_files'])->toBe(['payment-requests/1/attachments/gone.pdf']);

    expect($service->deleteOrphans($this->tenant))->toBe(['orphaned_files_deleted' => 1, 'bytes_freed' => 2]);
    Storage::disk('local')->assertMissing('payment-requests/1/attachments/orphan.pdf');
    Storage::disk('local')->assertExists('payment-requests/1/attachments/kept.pdf');
});

test('user admin unlocks suspended users, resends invites and forces logout', function () {
    Bus::fake();
    $service = app(TenantUserAdminService::class);
    $suspended = User::factory()->create(['status' => UserStatus::Suspended]);
    $invited = User::factory()->create(['status' => UserStatus::Invited, 'is_oidc_user' => true]);

    $service->unlock($this->tenant, $suspended->id);
    expect($suspended->fresh()->status)->toBe(UserStatus::Active);

    $service->resendInvite($this->tenant, $invited->id);
    Bus::assertDispatched(InviteUserToIamJob::class, fn($job) => $job->userId === $invited->id);

    $this->mock(SessionInvalidatorInterface::class)->shouldReceive('invalidate')->once()->with($invited->id);
    app(TenantUserAdminService::class)->forceLogout($this->tenant, $invited->id);

    expect(collect($service->list($this->tenant, $invited->email))->pluck('id')->all())->toBe([$invited->id]);
});

test('user admin refuses to unlock a user who is not suspended', function () {
    app(TenantUserAdminService::class)->unlock($this->tenant, $this->user->id);
})->throws(LogicException::class);

test('maintenance mode blocks requests but lets impersonation through', function () {
    $announcements = app(TenantAnnouncementService::class);
    $announcements->update($this->tenant, ['maintenance_mode' => true, 'maintenance_message' => 'Back at 5', 'announcement_message' => 'Heads up', 'announcement_level' => 'warning']);
    $middleware = app(EnsureTenantIsNotInMaintenance::class);
    $next = fn() => response('ok');
    $request = fn(string $uri) => tap(Request::create($uri), fn($r) => $r->setLaravelSession(app('session.store')));

    $blocked = $middleware->handle($request('/dashboard'), $next);
    expect($blocked->getStatusCode())->toBe(503)->and($blocked->getContent())->toContain('Back at 5');

    $json = $middleware->handle(Request::create('/api/me', 'GET', server: ['HTTP_ACCEPT' => 'application/json']), $next);
    expect($json->getStatusCode())->toBe(503);

    expect($middleware->handle($request('/impersonate/abc'), $next)->getContent())->toBe('ok');

    $announcements->update($this->tenant, ['maintenance_mode' => false]);
    expect($middleware->handle($request('/dashboard'), $next)->getContent())->toBe('ok');
    expect($announcements->get($this->tenant)['announcement_message'])->toBeEmpty();
});

test('failed job service only touches jobs that belong to the tenant', function () {
    $service = app(TenantFailedJobService::class);

    $service->retry($this->tenant, 'central', 'not-this-tenants-job');
})->throws(InvalidArgumentException::class);
