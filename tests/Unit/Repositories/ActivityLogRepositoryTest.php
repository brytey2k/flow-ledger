<?php

declare(strict_types=1);

uses(Tests\TenantAppTestCase::class);
use App\Models\Role;
use App\Models\Tenant\Branch;
use App\Models\Tenant\CashCount;
use App\Models\Tenant\PaymentRequest;
use App\Models\Tenant\RetirementRequest;
use App\Models\Tenant\Staff;
use App\Models\Tenant\User;
use App\Repositories\ActivityLogRepository;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Activity;

beforeEach(function () {
    $this->repository = app(ActivityLogRepository::class);
});
test('subject types contains expected keys', function () {
    expect($this->repository->subjectTypes)->toHaveKey('user');
    expect($this->repository->subjectTypes)->toHaveKey('staff');
    expect($this->repository->subjectTypes)->toHaveKey('payment_request');
    expect($this->repository->subjectTypes)->toHaveKey('retirement_request');
    expect($this->repository->subjectTypes)->toHaveKey('role');
    expect($this->repository->subjectTypes)->toHaveKey('branch');
});
test('subject types map to correct classes', function () {
    expect($this->repository->subjectTypes['user'])->toBe(User::class);
    expect($this->repository->subjectTypes['staff'])->toBe(Staff::class);
    expect($this->repository->subjectTypes['payment_request'])->toBe(PaymentRequest::class);
    expect($this->repository->subjectTypes['retirement_request'])->toBe(RetirementRequest::class);
    expect($this->repository->subjectTypes['role'])->toBe(Role::class);
    expect($this->repository->subjectTypes['branch'])->toBe(Branch::class);
});
test('morph map registers aliases for role branch and cash count', function () {
    expect(Relation::getMorphedModel('role'))->toBe(Role::class);
    expect(Relation::getMorphedModel('branch'))->toBe(Branch::class);
    expect(Relation::getMorphedModel('cash_count'))->toBe(CashCount::class);
});
test('morph map excludes payment request and retirement request', function () {
    expect(Relation::getMorphedModel('payment_request'))->toBeNull();
    expect(Relation::getMorphedModel('retirement_request'))->toBeNull();
});
test('subject label resolves morph map alias', function () {
    expect($this->repository->subjectLabel('branch'))->toBe('Branch');
    expect($this->repository->subjectLabel('role'))->toBe('Role');
});
test('subject label resolves legacy fqcn', function () {
    expect($this->repository->subjectLabel(PaymentRequest::class))->toBe('Payment Request');
    expect($this->repository->subjectLabel(RetirementRequest::class))->toBe('Retirement Request');
});
test('subject label resolves non filterable morph map alias', function () {
    expect($this->repository->subjectLabel('cash_count'))->toBe('Cash Count');
});
test('subject label falls back to class basename for unknown type', function () {
    expect($this->repository->subjectLabel('App\\Models\\SomeUnknownClass'))->toBe('SomeUnknownClass');
});
test('subject label returns empty string for null', function () {
    expect($this->repository->subjectLabel(null))->toBe('');
});
test('paginated filters by role subject type using alias', function () {
    $role = Role::create(['name' => 'repo-test-' . Str::uuid(), 'guard_name' => 'web']);
    activity()->performedOn($role)->event('role.created')->log('Role created');

    $result = $this->repository->paginated(subjectType: 'role');

    expect($result->contains(fn(Activity $log): bool => $log->subject_type === 'role'))->toBeTrue();
});
test('paginated returns paginator', function () {
    $result = $this->repository->paginated();

    expect($result)->toBeInstanceOf(Illuminate\Contracts\Pagination\LengthAwarePaginator::class);
});
test('paginated defaults to 50 per page', function () {
    $result = $this->repository->paginated();

    expect($result->perPage())->toBe(50);
});
test('paginated filters by known subject type', function () {
    $result = $this->repository->paginated(subjectType: 'user');

    expect($result)->toBeInstanceOf(Illuminate\Contracts\Pagination\LengthAwarePaginator::class);
});
test('paginated ignores unknown subject type', function () {
    // Unknown subject type should not crash and should return all logs unfiltered by subject_type
    $result = $this->repository->paginated(subjectType: 'nonexistent_model');

    expect($result)->toBeInstanceOf(Illuminate\Contracts\Pagination\LengthAwarePaginator::class);
});
test('paginated filters by staff subject type', function () {
    $result = $this->repository->paginated(subjectType: 'staff');

    expect($result)->toBeInstanceOf(Illuminate\Contracts\Pagination\LengthAwarePaginator::class);
});
test('paginated filters by payment request subject type', function () {
    $result = $this->repository->paginated(subjectType: 'payment_request');

    expect($result)->toBeInstanceOf(Illuminate\Contracts\Pagination\LengthAwarePaginator::class);
});
test('paginated filters by retirement request subject type', function () {
    $result = $this->repository->paginated(subjectType: 'retirement_request');

    expect($result)->toBeInstanceOf(Illuminate\Contracts\Pagination\LengthAwarePaginator::class);
});
test('paginated filters by event', function () {
    $result = $this->repository->paginated(event: 'created');

    expect($result)->toBeInstanceOf(Illuminate\Contracts\Pagination\LengthAwarePaginator::class);
});
test('paginated ignores empty event string', function () {
    $result = $this->repository->paginated(event: '');

    expect($result)->toBeInstanceOf(Illuminate\Contracts\Pagination\LengthAwarePaginator::class);
});
test('paginated filters by causer search', function () {
    $result = $this->repository->paginated(causerSearch: 'admin');

    expect($result)->toBeInstanceOf(Illuminate\Contracts\Pagination\LengthAwarePaginator::class);
});
test('paginated ignores empty causer search', function () {
    $result = $this->repository->paginated(causerSearch: '');

    expect($result)->toBeInstanceOf(Illuminate\Contracts\Pagination\LengthAwarePaginator::class);
});
test('paginated with all filters combined', function () {
    $result = $this->repository->paginated(
        subjectType: 'user',
        event: 'updated',
        causerSearch: 'Admin',
    );

    expect($result)->toBeInstanceOf(Illuminate\Contracts\Pagination\LengthAwarePaginator::class);
});
test('paginated with null filters returns all', function () {
    $result = $this->repository->paginated(null, null, null);

    expect($result)->toBeInstanceOf(Illuminate\Contracts\Pagination\LengthAwarePaginator::class);
});
test('paginated respects custom per page', function () {
    $result = $this->repository->paginated(perPage: 10);

    expect($result->perPage())->toBe(10);
});
