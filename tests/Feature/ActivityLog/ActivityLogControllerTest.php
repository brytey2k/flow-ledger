<?php

declare(strict_types=1);

namespace Tests\Feature\ActivityLog;

use App\Enums\Tenant\PermissionKey;
use Tests\TenantAppTestCase;

class ActivityLogControllerTest extends TenantAppTestCase
{
    // ── Authentication ────────────────────────────────────────────────────────

    public function test_guest_is_redirected_from_activity_log_index(): void
    {
        $this->get(route('activity-log.index'))->assertRedirect(route('login'));
    }

    // ── Authorization ─────────────────────────────────────────────────────────

    public function test_user_without_permission_cannot_access_activity_log(): void
    {
        $this->role->revokePermissionTo(PermissionKey::AccessActivityLog->value);

        $this->actingAs($this->user)
            ->get(route('activity-log.index'))
            ->assertForbidden();
    }

    // ── Happy path ────────────────────────────────────────────────────────────

    public function test_authorized_user_can_view_activity_log_index(): void
    {
        $this->actingAs($this->user)
            ->get(route('activity-log.index'))
            ->assertOk();
    }

    public function test_activity_log_index_passes_logs_to_view(): void
    {
        $this->actingAs($this->user)
            ->get(route('activity-log.index'))
            ->assertOk()
            ->assertViewHas('logs')
            ->assertViewHas('subjectLabels');
    }

    // ── Filter variants ───────────────────────────────────────────────────────

    public function test_activity_log_can_be_filtered_by_subject_type(): void
    {
        $this->actingAs($this->user)
            ->get(route('activity-log.index', ['subject_type' => 'payment_request']))
            ->assertOk();
    }

    public function test_activity_log_can_be_filtered_by_unknown_subject_type(): void
    {
        // An unknown subject_type should not crash — repository ignores it
        $this->actingAs($this->user)
            ->get(route('activity-log.index', ['subject_type' => 'nonexistent_type']))
            ->assertOk();
    }

    public function test_activity_log_can_be_filtered_by_event(): void
    {
        $this->actingAs($this->user)
            ->get(route('activity-log.index', ['event' => 'created']))
            ->assertOk();
    }

    public function test_activity_log_can_be_filtered_by_causer(): void
    {
        $this->actingAs($this->user)
            ->get(route('activity-log.index', ['causer' => $this->user->first_name]))
            ->assertOk();
    }

    public function test_activity_log_can_be_filtered_by_all_params_combined(): void
    {
        $this->actingAs($this->user)
            ->get(route('activity-log.index', [
                'subject_type' => 'staff',
                'event' => 'updated',
                'causer' => 'Admin',
            ]))
            ->assertOk();
    }

    public function test_activity_log_with_empty_filter_params_returns_ok(): void
    {
        // Filled check: empty strings should be treated as absent
        $this->actingAs($this->user)
            ->get(route('activity-log.index', [
                'subject_type' => '',
                'event' => '',
                'causer' => '',
            ]))
            ->assertOk();
    }

    public function test_activity_log_subject_labels_map_contains_all_subject_types(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('activity-log.index'))
            ->assertOk();

        $subjectLabels = $response->viewData('subjectLabels');

        $this->assertArrayHasKey('User', $subjectLabels);
        $this->assertArrayHasKey('Staff', $subjectLabels);
        $this->assertArrayHasKey('PaymentRequest', $subjectLabels);
        $this->assertArrayHasKey('RetirementRequest', $subjectLabels);
    }
}
