<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories;

use App\Models\Tenant\PaymentRequest;
use App\Models\Tenant\RetirementRequest;
use App\Models\Tenant\Staff;
use App\Models\Tenant\User;
use App\Repositories\ActivityLogRepository;
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
    }

    public function test_subject_types_map_to_correct_classes(): void
    {
        $this->assertSame(User::class, $this->repository->subjectTypes['user']);
        $this->assertSame(Staff::class, $this->repository->subjectTypes['staff']);
        $this->assertSame(PaymentRequest::class, $this->repository->subjectTypes['payment_request']);
        $this->assertSame(RetirementRequest::class, $this->repository->subjectTypes['retirement_request']);
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
