<?php

declare(strict_types=1);

uses(Tests\LandlordTestCase::class);
use App\Jobs\RefreshTenantHealthJob;
use App\Models\Landlord\TenantHealthSnapshot;
use App\Services\Backup\BackupService;
use App\Services\PlatformOverviewService;
use App\Services\TenantHealthService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;

function overviewSnapshot(array $overrides = []): array
{
    return array_merge([
        'payment_requests' => [], 'retirements' => [], 'blocked_stages' => 0, 'stale_stages' => 0,
        'overdue_retirements' => 0, 'failed_jobs' => 0, 'last_activity_at' => null, 'attachments' => 0,
        'storage_bytes' => 0, 'users' => [], 'last_backup_at' => now()->subHour()->toDateTimeString(),
        'last_backup_verified_at' => now()->subDay()->toDateTimeString(),
    ], $overrides);
}

function overviewService(): PlatformOverviewService
{
    return app(PlatformOverviewService::class);
}

beforeEach(function () {
    Cache::forget('platform-overview.backup-problems');
    $this->mock(BackupService::class)->shouldReceive('problems')->andReturn([]);
});

test('guests are redirected from the platform overview', function () {
    $this->get(route('landlord.overview.index'))->assertRedirect();
    $this->post(route('landlord.overview.refresh'))->assertRedirect();
});

test('a reading of a healthy customer is stored as all clear', function () {
    $this->mock(TenantHealthService::class)->shouldReceive('snapshot')->once()->andReturn(overviewSnapshot());

    $reading = overviewService()->refresh($this->tenant);

    expect($reading->needs_attention)->toBeFalse()->and($reading->attention)->toBe([]);
});

test('a reading flags stuck work, failed jobs and old backups', function () {
    $this->mock(TenantHealthService::class)->shouldReceive('snapshot')->once()->andReturn(overviewSnapshot([
        'blocked_stages' => 2, 'failed_jobs' => 3, 'last_backup_at' => now()->subDays(2)->toDateTimeString(),
    ]));

    $reading = overviewService()->refresh($this->tenant);

    expect($reading->needs_attention)->toBeTrue()
        ->and($reading->attention)->toBe(['2 blocked workflow stage(s)', '3 failed job(s)', 'No recent backup']);
});

test('a customer whose database cannot be read is stored as an error instead of failing', function () {
    $this->mock(TenantHealthService::class)->shouldReceive('snapshot')->once()->andThrow(new RuntimeException('database missing'));

    $reading = overviewService()->refresh($this->tenant);

    expect($reading->attention)->toBe(['Database could not be read'])->and($reading->error)->toBe('database missing');
});

test('refreshing twice keeps one reading per customer', function () {
    $this->mock(TenantHealthService::class)->shouldReceive('snapshot')->twice()->andReturn(overviewSnapshot());

    overviewService()->refresh($this->tenant);
    overviewService()->refresh($this->tenant);

    expect(TenantHealthSnapshot::query()->where('tenant_id', $this->tenant->id)->count())->toBe(1);
});

test('the overview shows stored readings without opening any customer database', function () {
    $this->mock(TenantHealthService::class)->shouldNotReceive('snapshot');
    TenantHealthSnapshot::create([
        'tenant_id' => $this->tenant->id, 'snapshot' => overviewSnapshot(['failed_jobs' => 3]),
        'attention' => ['3 failed job(s)'], 'needs_attention' => true, 'captured_at' => now()->subMinutes(4),
    ]);

    $this->actingAs($this->landlordUser, 'landlord')->get(route('landlord.overview.index'))
        ->assertOk()->assertSee($this->tenant->id)->assertSee('3 failed job(s)')->assertSee('Read 4 minutes ago');
});

test('a customer with no reading yet is listed as not read', function () {
    $this->actingAs($this->landlordUser, 'landlord')->get(route('landlord.overview.index'))
        ->assertOk()->assertSee($this->tenant->id)->assertSee('Not read yet');
});

test('backup problems are shown and cached between page loads', function () {
    $backups = Mockery::mock(BackupService::class);
    $backups->shouldReceive('problems')->once()->andReturn(['some_db: no backup exists.']);
    $this->app->instance(BackupService::class, $backups);

    $page = $this->actingAs($this->landlordUser, 'landlord');
    $page->get(route('landlord.overview.index'))->assertSee('some_db: no backup exists.');
    $page->get(route('landlord.overview.index'))->assertSee('some_db: no backup exists.');
});

test('refresh all queues one job per customer and drops readings of deleted customers', function () {
    Queue::fake();
    TenantHealthSnapshot::create(['tenant_id' => 'deleted-customer', 'snapshot' => null, 'attention' => [], 'captured_at' => now()]);

    $queued = overviewService()->queueRefreshAll();

    Queue::assertPushed(RefreshTenantHealthJob::class, fn(RefreshTenantHealthJob $job): bool => $job->tenantId === $this->tenant->id);
    expect($queued)->toBeGreaterThanOrEqual(1)
        ->and(TenantHealthSnapshot::query()->where('tenant_id', 'deleted-customer')->exists())->toBeFalse();
});

test('the refresh button queues readings', function () {
    Queue::fake();

    $this->actingAs($this->landlordUser, 'landlord')->post(route('landlord.overview.refresh'))
        ->assertRedirect(route('landlord.overview.index'))->assertSessionHas('success');

    Queue::assertPushed(RefreshTenantHealthJob::class);
});

test('the refresh job stores a reading and skips a deleted customer', function () {
    $this->mock(TenantHealthService::class)->shouldReceive('snapshot')->once()->andReturn(overviewSnapshot());

    (new RefreshTenantHealthJob($this->tenant->id))->handle(overviewService());
    (new RefreshTenantHealthJob('no-such-customer'))->handle(overviewService());

    expect(TenantHealthSnapshot::query()->where('tenant_id', $this->tenant->id)->exists())->toBeTrue()
        ->and(TenantHealthSnapshot::query()->where('tenant_id', 'no-such-customer')->exists())->toBeFalse();
});

test('the health refresh command is scheduled', function () {
    $this->artisan('schedule:list')->expectsOutputToContain('health:refresh');
});
