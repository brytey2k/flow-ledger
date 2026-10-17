<?php

declare(strict_types=1);

uses(Tests\LandlordTestCase::class);
use App\Enums\AuditLogStatus;
use App\Enums\TenantDataTool;
use App\Jobs\RunTenantDataToolJob;
use App\Models\Landlord\AuditLog;
use App\Services\TenantDataToolService;
use Illuminate\Support\Facades\Bus;

function dataToolRunUrl(TenantDataTool $tool = TenantDataTool::ClearPaymentRequests): string
{
    return route('landlord.tenants.data-tools.run', [test()->tenant, $tool->value]);
}

test('guest is redirected from data tools', function () {
    $this->get(route('landlord.tenants.data-tools.index', $this->tenant))->assertRedirect();
    $this->post(dataToolRunUrl())->assertRedirect();
});

test('authenticated user can view data tools page', function () {
    $this->actingAs($this->landlordUser, 'landlord')
        ->get(route('landlord.tenants.data-tools.index', $this->tenant))
        ->assertOk()
        ->assertViewHas('tools')
        ->assertSee('Clear payment requests');
});

test('run is rejected when the confirmation name does not match', function () {
    Bus::fake();

    $this->actingAs($this->landlordUser, 'landlord')
        ->post(dataToolRunUrl(), ['confirm_tenant_name' => 'nope'])
        ->assertRedirect()
        ->assertSessionHas('error');

    Bus::assertNotDispatched(RunTenantDataToolJob::class);
    expect(AuditLog::count())->toBe(0);
});

test('run records an audit log entry and dispatches the job', function () {
    Bus::fake();

    $this->actingAs($this->landlordUser, 'landlord')
        ->post(dataToolRunUrl(), [
            'confirm_tenant_name' => 'Landlord Test Tenant',
            'statuses' => ['draft'],
            'keep_drafts' => '1',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $log = AuditLog::firstOrFail();
    expect($log->tenant_id)->toBe($this->tenant->getTenantKey())
        ->and($log->landlord_user_email)->toBe($this->landlordUser->email)
        ->and($log->action)->toBe('clear-payment-requests')
        ->and($log->status)->toBe(AuditLogStatus::Queued)
        ->and($log->filters['statuses'])->toBe(['draft'])
        ->and($log->filters['keep_drafts'])->toBeTrue();
    Bus::assertDispatched(RunTenantDataToolJob::class, fn($job) => $job->auditLogId === $log->id);
});

test('run is blocked while another run is active for the tenant', function () {
    Bus::fake();
    AuditLog::create(['tenant_id' => $this->tenant->getTenantKey(), 'action' => 'clear-activity-log', 'status' => AuditLogStatus::Running]);

    $this->actingAs($this->landlordUser, 'landlord')
        ->post(dataToolRunUrl(), ['confirm_tenant_name' => 'Landlord Test Tenant'])
        ->assertSessionHas('error');

    Bus::assertNotDispatched(RunTenantDataToolJob::class);
});

test('non destructive tool needs no confirmation', function () {
    Bus::fake();

    $this->actingAs($this->landlordUser, 'landlord')
        ->post(dataToolRunUrl(TenantDataTool::RecalculateCashbookBalances))
        ->assertSessionHas('success');

    Bus::assertDispatched(RunTenantDataToolJob::class);
});

test('invalid status filter is rejected', function () {
    Bus::fake();

    $this->actingAs($this->landlordUser, 'landlord')
        ->post(dataToolRunUrl(), ['confirm_tenant_name' => 'Landlord Test Tenant', 'statuses' => ['bogus']])
        ->assertSessionHasErrors('statuses.0');
});

test('preview flashes counts without queuing anything', function () {
    Bus::fake();
    $this->mock(TenantDataToolService::class)
        ->shouldReceive('preview')->once()->andReturn(['payment_requests' => 3]);

    $this->actingAs($this->landlordUser, 'landlord')
        ->post(route('landlord.tenants.data-tools.preview', [$this->tenant, 'clear-payment-requests']))
        ->assertRedirect()
        ->assertSessionHas('preview', ['tool' => 'clear-payment-requests', 'counts' => ['payment_requests' => 3]]);

    Bus::assertNothingDispatched();
    expect(AuditLog::count())->toBe(0);
});
