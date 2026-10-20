<?php

declare(strict_types=1);

uses(Tests\LandlordTestCase::class);
use App\Jobs\RecordQueueHeartbeatJob;
use App\Services\Backup\BackupService;
use App\Services\PlatformHeartbeatService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    Cache::store(config()->string('cache.default'))->forget(PlatformHeartbeatService::SCHEDULER_KEY);
    Cache::store(config()->string('cache.default'))->forget(PlatformHeartbeatService::QUEUE_KEY);
});

test('nothing recorded yet counts as not running', function () {
    $status = app(PlatformHeartbeatService::class)->status();

    expect($status['ok'])->toBeFalse()->and($status['scheduler']['last_seen_at'])->toBeNull();
});

test('fresh beats count as running and old ones as stale', function () {
    $service = app(PlatformHeartbeatService::class);
    $service->recordScheduler();
    $service->recordQueue();

    expect($service->status()['ok'])->toBeTrue();

    $this->travel(PlatformHeartbeatService::SCHEDULER_STALE_AFTER_SECONDS + 1)->seconds();
    $status = $service->status();

    expect($status['scheduler']['ok'])->toBeFalse()->and($status['queue']['ok'])->toBeTrue();
});

test('the record command stamps the scheduler and queues the queue beat', function () {
    Queue::fake();

    $this->artisan('heartbeat:record')->assertSuccessful();

    expect(app(PlatformHeartbeatService::class)->status()['scheduler']['ok'])->toBeTrue();
    Queue::assertPushed(RecordQueueHeartbeatJob::class);
});

test('the queue job stamps the queue beat', function () {
    (new RecordQueueHeartbeatJob())->handle(app(PlatformHeartbeatService::class));

    expect(app(PlatformHeartbeatService::class)->status()['queue']['ok'])->toBeTrue();
});

test('the check command reports stopped queue workers while the scheduler runs', function () {
    app(PlatformHeartbeatService::class)->recordScheduler();
    $reported = [];
    $this->app->make(Illuminate\Contracts\Debug\ExceptionHandler::class)->reportable(function (Throwable $e) use (&$reported): void {
        $reported[] = $e->getMessage();
    });

    $this->artisan('heartbeat:check')->assertFailed();

    expect($reported)->toHaveCount(1);
});

test('the check command stays quiet when everything runs or the scheduler itself is down', function () {
    $this->artisan('heartbeat:check')->assertSuccessful();

    $service = app(PlatformHeartbeatService::class);
    $service->recordScheduler();
    $service->recordQueue();

    $this->artisan('heartbeat:check')->assertSuccessful();
});

test('the monitor endpoint answers 503 until both are running, then 200', function () {
    $this->getJson(route('landlord.heartbeat'))->assertStatus(503)->assertExactJson(['scheduler' => 'stale', 'queue' => 'stale']);

    $service = app(PlatformHeartbeatService::class);
    $service->recordScheduler();
    $service->recordQueue();

    $this->getJson(route('landlord.heartbeat'))->assertOk()->assertExactJson(['scheduler' => 'ok', 'queue' => 'ok']);
});

test('the overview warns when background processing has stopped', function () {
    $this->mock(BackupService::class)->shouldReceive('problems')->andReturn([]);
    Cache::forget('platform-overview.backup-problems');

    $this->actingAs($this->landlordUser, 'landlord')->get(route('landlord.overview.index'))
        ->assertOk()->assertSee('Background processing')->assertSee('not seen yet');
});

test('both heartbeat commands are scheduled', function () {
    $this->artisan('schedule:list')->expectsOutputToContain('heartbeat:record')->expectsOutputToContain('heartbeat:check');
});
