<?php

declare(strict_types=1);

uses(Tests\LandlordTestCase::class);
use App\Enums\AuditLogStatus;
use App\Enums\TenantFixUp;
use App\Models\Landlord\AuditLog;
use App\Services\TenantAnnouncementService;
use App\Services\TenantFailedJobService;
use App\Services\TenantFixUpService;
use App\Services\TenantHealthService;
use App\Services\TenantStorageService;
use App\Services\TenantUserAdminService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Storage;

function opsAsLandlord(): mixed
{
    return test()->actingAs(test()->landlordUser, 'landlord');
}

test('guests are redirected from every operations page', function (string $routeName) {
    $this->get(route($routeName, $this->tenant))->assertRedirect();
})->with([
    'landlord.tenants.fix-ups.index',
    'landlord.tenants.health.index',
    'landlord.tenants.failed-jobs.index',
    'landlord.tenants.storage.index',
    'landlord.tenants.user-admin.index',
    'landlord.tenants.announcements.edit',
]);

test('fix-ups page lists every fix-up', function () {
    opsAsLandlord()->get(route('landlord.tenants.fix-ups.index', $this->tenant))
        ->assertOk()
        ->assertSee('Reverse a disbursement')
        ->assertSee('Re-open a settled retirement');
});

test('running a fix-up is audited with the landlord user', function () {
    $this->mock(TenantFixUpService::class)
        ->shouldReceive('reverseDisbursement')->once()->with(Mockery::any(), 42, $this->landlordUser->email)
        ->andReturn(['disbursements_reversed' => 1, 'cashbook_entries_removed' => 1]);

    opsAsLandlord()->post(route('landlord.tenants.fix-ups.run', [$this->tenant, TenantFixUp::ReverseDisbursement->value]), ['record_id' => 42])
        ->assertRedirect()
        ->assertSessionHas('success');

    $log = AuditLog::firstOrFail();
    expect($log->action)->toBe('reverse-disbursement')
        ->and($log->status)->toBe(AuditLogStatus::Completed)
        ->and($log->landlord_user_email)->toBe($this->landlordUser->email)
        ->and($log->filters['record_id'])->toBe(42)
        ->and($log->result)->toBe(['disbursements_reversed' => 1, 'cashbook_entries_removed' => 1]);
});

test('a refused fix-up flashes the reason and is logged as failed', function () {
    $this->mock(TenantFixUpService::class)
        ->shouldReceive('reopenRetirement')->once()->andThrow(new LogicException('Only a settled retirement can be re-opened.'));

    opsAsLandlord()->post(route('landlord.tenants.fix-ups.run', [$this->tenant, 'reopen-retirement']), ['record_id' => 1])
        ->assertSessionHas('error', 'Only a settled retirement can be re-opened.');

    expect(AuditLog::firstOrFail()->status)->toBe(AuditLogStatus::Failed);
});

test('a fix-up on a missing record reports not found', function () {
    $this->mock(TenantFixUpService::class)->shouldReceive('forceCancel')->once()->andThrow(new ModelNotFoundException());

    opsAsLandlord()->post(route('landlord.tenants.fix-ups.run', [$this->tenant, 'force-cancel-request']), ['kind' => 'payment', 'record_id' => 999])
        ->assertSessionHas('error');
});

test('fix-up validates its input', function () {
    opsAsLandlord()->post(route('landlord.tenants.fix-ups.run', [$this->tenant, 'force-cancel-request']), ['record_id' => 1])
        ->assertSessionHasErrors('kind');
    opsAsLandlord()->post(route('landlord.tenants.fix-ups.run', [$this->tenant, 'reopen-retirement']), [])
        ->assertSessionHasErrors('record_id');
});

test('health page renders the snapshot', function () {
    $this->mock(TenantHealthService::class)->shouldReceive('snapshot')->once()->andReturn([
        'payment_requests' => ['draft' => 3], 'retirements' => [], 'blocked_stages' => 2, 'stale_stages' => 1,
        'overdue_retirements' => 4, 'failed_jobs' => 5, 'last_activity_at' => null, 'attachments' => 6,
        'storage_bytes' => 2048, 'users' => ['active' => 7],
        'last_backup_at' => '2026-10-17 06:00:00', 'last_backup_verified_at' => null,
    ]);

    opsAsLandlord()->get(route('landlord.tenants.health.index', $this->tenant))
        ->assertOk()->assertSee('Blocked workflow stages')->assertSee('Overdue retirements')->assertSee('Draft')->assertSee('Last backup')->assertSee('Last restore test');
});

test('failed jobs page lists jobs and retry is audited', function () {
    $service = $this->mock(TenantFailedJobService::class);
    $service->shouldReceive('list')->once()->andReturn([[
        'scope' => 'central', 'uuid' => 'abc-123', 'queue' => 'default', 'job' => 'App\\Jobs\\Example',
        'exception' => 'Boom', 'failed_at' => '2026-10-12 10:00:00',
    ]]);
    $service->shouldReceive('retry')->once()->with(Mockery::any(), 'central', 'abc-123');

    opsAsLandlord()->get(route('landlord.tenants.failed-jobs.index', $this->tenant))->assertOk()->assertSee('App\\Jobs\\Example')->assertSee('Boom');
    opsAsLandlord()->post(route('landlord.tenants.failed-jobs.retry', [$this->tenant, 'central', 'abc-123']))->assertSessionHas('success');

    expect(AuditLog::firstOrFail()->action)->toBe('retry-failed-job');
});

test('failed job discard rejects jobs from other tenants', function () {
    $this->mock(TenantFailedJobService::class)->shouldReceive('discard')->once()->andThrow(new InvalidArgumentException('That failed job does not belong to this tenant.'));

    opsAsLandlord()->delete(route('landlord.tenants.failed-jobs.discard', [$this->tenant, 'central', 'nope']))->assertSessionHas('error');
});

test('storage page renders and cleanup is audited', function () {
    $service = $this->mock(TenantStorageService::class);
    $service->shouldReceive('report')->once()->andReturn([
        'usage' => [['type' => 'PaymentRequest', 'files' => 2, 'bytes' => 2048]],
        'orphaned_files' => ['payment-requests/1/a.pdf'], 'orphaned_bytes' => 10, 'missing_files' => [],
    ]);
    $service->shouldReceive('deleteOrphans')->once()->andReturn(['orphaned_files_deleted' => 1, 'bytes_freed' => 10]);

    opsAsLandlord()->get(route('landlord.tenants.storage.index', $this->tenant))->assertOk()->assertSee('payment-requests/1/a.pdf');
    opsAsLandlord()->post(route('landlord.tenants.storage.cleanup', $this->tenant))->assertSessionHas('success');

    expect(AuditLog::firstOrFail()->result)->toBe(['orphaned_files_deleted' => 1, 'bytes_freed' => 10]);
});

test('user admin page lists users and actions are audited', function () {
    $service = $this->mock(TenantUserAdminService::class);
    $service->shouldReceive('list')->once()->andReturn([[
        'id' => 9, 'name' => 'Ada Lovelace', 'email' => 'ada@example.com', 'status' => 'suspended', 'sso_linked' => false, 'is_oidc_user' => true,
    ]]);
    $service->shouldReceive('unlock')->once()->with(Mockery::any(), 9);
    $service->shouldReceive('forceLogout')->once()->with(Mockery::any(), 9);

    opsAsLandlord()->get(route('landlord.tenants.user-admin.index', $this->tenant))
        ->assertOk()->assertSee('Ada Lovelace')->assertSee('SSO not linked yet')->assertSee('Unlock');
    opsAsLandlord()->post(route('landlord.tenants.user-admin.unlock', [$this->tenant, 9]))->assertSessionHas('success');
    opsAsLandlord()->post(route('landlord.tenants.user-admin.force-logout', [$this->tenant, 9]))->assertSessionHas('success');

    expect(AuditLog::pluck('action')->all())->toBe(['unlock-user', 'force-logout-user']);
});

test('user admin reports a business rule refusal', function () {
    $this->mock(TenantUserAdminService::class)->shouldReceive('unlock')->once()->andThrow(new LogicException('Only suspended users can be unlocked.'));

    opsAsLandlord()->post(route('landlord.tenants.user-admin.unlock', [$this->tenant, 1]))->assertSessionHas('error', 'Only suspended users can be unlocked.');
});

test('announcements can be edited and are audited', function () {
    opsAsLandlord()->get(route('landlord.tenants.announcements.edit', $this->tenant))->assertOk()->assertSee('Maintenance mode');

    opsAsLandlord()->put(route('landlord.tenants.announcements.update', $this->tenant), [
        'announcement_message' => 'Planned downtime tonight', 'announcement_level' => 'warning',
        'maintenance_mode' => '1', 'maintenance_message' => 'Back soon',
    ])->assertSessionHas('success');

    expect(app(TenantAnnouncementService::class)->get($this->tenant->fresh()))->toBe([
        'announcement_message' => 'Planned downtime tonight', 'announcement_level' => 'warning',
        'maintenance_mode' => true, 'maintenance_message' => 'Back soon',
    ]);
    expect(AuditLog::firstOrFail()->action)->toBe('update-announcements');
});

test('announcement level is validated', function () {
    opsAsLandlord()->put(route('landlord.tenants.announcements.update', $this->tenant), ['announcement_level' => 'loud'])
        ->assertSessionHasErrors('announcement_level');
});

test('export download is limited to the owning tenant and audited', function () {
    Storage::fake('local');
    Storage::disk('local')->put('tenant-exports/x/a.zip', 'zip');
    $log = AuditLog::create([
        'tenant_id' => $this->tenant->getTenantKey(), 'action' => 'export-tenant-data',
        'status' => AuditLogStatus::Completed, 'artifact_path' => 'tenant-exports/x/a.zip',
    ]);
    $other = AuditLog::create([
        'tenant_id' => 'someone-else', 'action' => 'export-tenant-data',
        'status' => AuditLogStatus::Completed, 'artifact_path' => 'tenant-exports/x/a.zip',
    ]);

    opsAsLandlord()->get(route('landlord.tenants.data-tools.download', [$this->tenant, $other]))->assertNotFound();
    opsAsLandlord()->get(route('landlord.tenants.data-tools.download', [$this->tenant, $log]))->assertOk()->assertDownload();

    expect(AuditLog::where('action', 'download-export')->count())->toBe(1);
});
