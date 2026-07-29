<?php

declare(strict_types=1);

uses(Tests\TenantAppTestCase::class);
use App\Enums\Tenant\PermissionKey;
use App\Models\Role;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Activity;

test('guest is redirected from activity log index', function () {
    $this->get(route('activity-log.index'))->assertRedirect(route('login'));
});
test('user without permission cannot access activity log', function () {
    $this->role->revokePermissionTo(PermissionKey::AccessActivityLog->value);

    $this->actingAs($this->user)
        ->get(route('activity-log.index'))
        ->assertForbidden();
});
test('authorized user can view activity log index', function () {
    $this->actingAs($this->user)
        ->get(route('activity-log.index'))
        ->assertOk();
});
test('activity log index passes logs to view', function () {
    $this->actingAs($this->user)
        ->get(route('activity-log.index'))
        ->assertOk()
        ->assertViewHas('logs')
        ->assertViewHas('subjectLabels');
});
test('activity log can be filtered by subject type', function () {
    $this->actingAs($this->user)
        ->get(route('activity-log.index', ['subject_type' => 'payment_request']))
        ->assertOk();
});
test('activity log can be filtered by unknown subject type', function () {
    // An unknown subject_type should not crash — repository ignores it
    $this->actingAs($this->user)
        ->get(route('activity-log.index', ['subject_type' => 'nonexistent_type']))
        ->assertOk();
});
test('activity log can be filtered by event', function () {
    $this->actingAs($this->user)
        ->get(route('activity-log.index', ['event' => 'created']))
        ->assertOk();
});
test('activity log can be filtered by causer', function () {
    $this->actingAs($this->user)
        ->get(route('activity-log.index', ['causer' => $this->user->first_name]))
        ->assertOk();
});
test('activity log can be filtered by all params combined', function () {
    $this->actingAs($this->user)
        ->get(route('activity-log.index', [
            'subject_type' => 'staff',
            'event' => 'updated',
            'causer' => 'Admin',
        ]))
        ->assertOk();
});
test('activity log with empty filter params returns ok', function () {
    // Filled check: empty strings should be treated as absent
    $this->actingAs($this->user)
        ->get(route('activity-log.index', [
            'subject_type' => '',
            'event' => '',
            'causer' => '',
        ]))
        ->assertOk();
});
test('activity log subject labels map contains all subject types', function () {
    $response = $this->actingAs($this->user)
        ->get(route('activity-log.index'))
        ->assertOk();

    $subjectLabels = $response->viewData('subjectLabels');

    expect($subjectLabels)->toHaveKey('User');
    expect($subjectLabels)->toHaveKey('Staff');
    expect($subjectLabels)->toHaveKey('PaymentRequest');
    expect($subjectLabels)->toHaveKey('RetirementRequest');
    expect($subjectLabels)->toHaveKey('Role');
    expect($subjectLabels)->toHaveKey('Branch');
});
test('guest is redirected from activity log show', function () {
    $role = Role::create(['name' => 'show-guest-' . Str::uuid(), 'guard_name' => 'web']);
    $log = activity()->performedOn($role)->event('role.created')->log('Role created');

    $this->get(route('activity-log.show', $log))->assertRedirect(route('login'));
});
test('user without permission cannot view activity log show', function () {
    $this->role->revokePermissionTo(PermissionKey::AccessActivityLog->value);
    $role = Role::create(['name' => 'show-forbidden-' . Str::uuid(), 'guard_name' => 'web']);
    $log = activity()->performedOn($role)->event('role.created')->log('Role created');

    $this->actingAs($this->user)
        ->get(route('activity-log.show', $log))
        ->assertForbidden();
});
test('authorized user can view activity log show', function () {
    $role = Role::create(['name' => 'show-ok-' . Str::uuid(), 'guard_name' => 'web']);
    $log = activity()->performedOn($role)->causedBy($this->user)->event('role.created')->log('Role created');

    $this->actingAs($this->user)
        ->get(route('activity-log.show', $log))
        ->assertOk()
        ->assertViewHas('log');
});
test('activity log show returns 404 for missing entry', function () {
    $missingId = (Activity::query()->max('id') ?? 0) + 1;

    $this->actingAs($this->user)
        ->get(route('activity-log.show', $missingId))
        ->assertNotFound();
});
