<?php

declare(strict_types=1);

uses(Tests\TenantAppTestCase::class);

use App\Enums\AuditLogStatus;
use App\Exceptions\ImmutableRecordException;
use App\Models\Landlord\AuditLog;
use App\Models\Tenant\Activity;

test('activity log entries cannot be edited', function () {
    $activity = activity()->log('something happened');
    $entry = Activity::query()->findOrFail($activity->getKey());

    expect(fn() => $entry->update(['description' => 'changed']))->toThrow(ImmutableRecordException::class);
    expect($entry->fresh()->description)->toBe('something happened');
});

test('activity log entries cannot be deleted', function () {
    $activity = activity()->log('something happened');
    $entry = Activity::query()->findOrFail($activity->getKey());

    expect(fn() => $entry->delete())->toThrow(ImmutableRecordException::class);
    expect(Activity::query()->whereKey($entry->getKey())->exists())->toBeTrue();
});

test('audit log entries can be finished once and then are locked', function () {
    $log = AuditLog::create(['tenant_id' => 't1', 'action' => 'export-data', 'status' => AuditLogStatus::Running]);

    $log->update(['status' => AuditLogStatus::Completed, 'result' => ['rows' => 1]]);

    expect(fn() => $log->update(['error' => 'tampered']))->toThrow(ImmutableRecordException::class);
});

test('audit log entries cannot be deleted', function () {
    $log = AuditLog::create(['tenant_id' => 't1', 'action' => 'export-data', 'status' => AuditLogStatus::Running]);

    expect(fn() => $log->delete())->toThrow(ImmutableRecordException::class);
});
