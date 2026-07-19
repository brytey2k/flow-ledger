<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories;

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
use Tests\TenantAppTestCase;

class ActivityLogRepositoryTest extends TenantAppTestCase
{
    private ActivityLogRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = app(ActivityLogRepository::class);
    }

    // ── subjectTypes map ──────────────────────────────────────────────────────

    public function test_subject_types_contains_expected_keys(): void
    {
        $this->assertArrayHasKey('user', $this->repository->subjectTypes);
        $this->assertArrayHasKey('staff', $this->repository->subjectTypes);
        $this->assertArrayHasKey('payment_request', $this->repository->subjectTypes);
        $this->assertArrayHasKey('retirement_request', $this->repository->subjectTypes);
        $this->assertArrayHasKey('role', $this->repository->subjectTypes);
        $this->assertArrayHasKey('branch', $this->repository->subjectTypes);
    }

    public function test_subject_types_map_to_correct_classes(): void
    {
        $this->assertSame(User::class, $this->repository->subjectTypes['user']);
        $this->assertSame(Staff::class, $this->repository->subjectTypes['staff']);
        $this->assertSame(PaymentRequest::class, $this->repository->subjectTypes['payment_request']);
        $this->assertSame(RetirementRequest::class, $this->repository->subjectTypes['retirement_request']);
        $this->assertSame(Role::class, $this->repository->subjectTypes['role']);
        $this->assertSame(Branch::class, $this->repository->subjectTypes['branch']);
    }

    // ── Morph map ─────────────────────────────────────────────────────────────

    public function test_morph_map_registers_aliases_for_role_branch_and_cash_count(): void
    {
        $this->assertSame(Role::class, Relation::getMorphedModel('role'));
        $this->assertSame(Branch::class, Relation::getMorphedModel('branch'));
        $this->assertSame(CashCount::class, Relation::getMorphedModel('cash_count'));
    }

    public function test_morph_map_excludes_payment_request_and_retirement_request(): void
    {
        $this->assertNull(Relation::getMorphedModel('payment_request'));
        $this->assertNull(Relation::getMorphedModel('retirement_request'));
    }

    // ── subjectLabel() ────────────────────────────────────────────────────────

    public function test_subject_label_resolves_morph_map_alias(): void
    {
        $this->assertSame('Branch', $this->repository->subjectLabel('branch'));
        $this->assertSame('Role', $this->repository->subjectLabel('role'));
    }

    public function test_subject_label_resolves_legacy_fqcn(): void
    {
        $this->assertSame('Payment Request', $this->repository->subjectLabel(PaymentRequest::class));
        $this->assertSame('Retirement Request', $this->repository->subjectLabel(RetirementRequest::class));
    }

    public function test_subject_label_resolves_non_filterable_morph_map_alias(): void
    {
        $this->assertSame('Cash Count', $this->repository->subjectLabel('cash_count'));
    }

    public function test_subject_label_falls_back_to_class_basename_for_unknown_type(): void
    {
        $this->assertSame('SomeUnknownClass', $this->repository->subjectLabel('App\\Models\\SomeUnknownClass'));
    }

    public function test_subject_label_returns_empty_string_for_null(): void
    {
        $this->assertSame('', $this->repository->subjectLabel(null));
    }

    // ── paginated() — role/branch subject types ──────────────────────────────

    public function test_paginated_filters_by_role_subject_type_using_alias(): void
    {
        $role = Role::create(['name' => 'repo-test-' . Str::uuid(), 'guard_name' => 'web']);
        activity()->performedOn($role)->event('role.created')->log('Role created');

        $result = $this->repository->paginated(subjectType: 'role');

        $this->assertTrue($result->contains(fn(Activity $log): bool => $log->subject_type === 'role'));
    }

    // ── paginated() — no filters ──────────────────────────────────────────────

    public function test_paginated_returns_paginator(): void
    {
        $result = $this->repository->paginated();

        $this->assertInstanceOf(\Illuminate\Contracts\Pagination\LengthAwarePaginator::class, $result);
    }

    public function test_paginated_defaults_to_50_per_page(): void
    {
        $result = $this->repository->paginated();

        $this->assertSame(50, $result->perPage());
    }

    // ── paginated() — subject_type filter ────────────────────────────────────

    public function test_paginated_filters_by_known_subject_type(): void
    {
        $result = $this->repository->paginated(subjectType: 'user');

        $this->assertInstanceOf(\Illuminate\Contracts\Pagination\LengthAwarePaginator::class, $result);
    }

    public function test_paginated_ignores_unknown_subject_type(): void
    {
        // Unknown subject type should not crash and should return all logs unfiltered by subject_type
        $result = $this->repository->paginated(subjectType: 'nonexistent_model');

        $this->assertInstanceOf(\Illuminate\Contracts\Pagination\LengthAwarePaginator::class, $result);
    }

    public function test_paginated_filters_by_staff_subject_type(): void
    {
        $result = $this->repository->paginated(subjectType: 'staff');

        $this->assertInstanceOf(\Illuminate\Contracts\Pagination\LengthAwarePaginator::class, $result);
    }

    public function test_paginated_filters_by_payment_request_subject_type(): void
    {
        $result = $this->repository->paginated(subjectType: 'payment_request');

        $this->assertInstanceOf(\Illuminate\Contracts\Pagination\LengthAwarePaginator::class, $result);
    }

    public function test_paginated_filters_by_retirement_request_subject_type(): void
    {
        $result = $this->repository->paginated(subjectType: 'retirement_request');

        $this->assertInstanceOf(\Illuminate\Contracts\Pagination\LengthAwarePaginator::class, $result);
    }

    // ── paginated() — event filter ────────────────────────────────────────────

    public function test_paginated_filters_by_event(): void
    {
        $result = $this->repository->paginated(event: 'created');

        $this->assertInstanceOf(\Illuminate\Contracts\Pagination\LengthAwarePaginator::class, $result);
    }

    public function test_paginated_ignores_empty_event_string(): void
    {
        $result = $this->repository->paginated(event: '');

        $this->assertInstanceOf(\Illuminate\Contracts\Pagination\LengthAwarePaginator::class, $result);
    }

    // ── paginated() — causer filter ───────────────────────────────────────────

    public function test_paginated_filters_by_causer_search(): void
    {
        $result = $this->repository->paginated(causerSearch: 'admin');

        $this->assertInstanceOf(\Illuminate\Contracts\Pagination\LengthAwarePaginator::class, $result);
    }

    public function test_paginated_ignores_empty_causer_search(): void
    {
        $result = $this->repository->paginated(causerSearch: '');

        $this->assertInstanceOf(\Illuminate\Contracts\Pagination\LengthAwarePaginator::class, $result);
    }

    // ── paginated() — combined filters ───────────────────────────────────────

    public function test_paginated_with_all_filters_combined(): void
    {
        $result = $this->repository->paginated(
            subjectType: 'user',
            event: 'updated',
            causerSearch: 'Admin',
        );

        $this->assertInstanceOf(\Illuminate\Contracts\Pagination\LengthAwarePaginator::class, $result);
    }

    public function test_paginated_with_null_filters_returns_all(): void
    {
        $result = $this->repository->paginated(null, null, null);

        $this->assertInstanceOf(\Illuminate\Contracts\Pagination\LengthAwarePaginator::class, $result);
    }

    // ── paginated() — custom per_page ─────────────────────────────────────────

    public function test_paginated_respects_custom_per_page(): void
    {
        $result = $this->repository->paginated(perPage: 10);

        $this->assertSame(10, $result->perPage());
    }
}
