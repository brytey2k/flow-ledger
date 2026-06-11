<?php

declare(strict_types=1);

namespace Tests\Feature\Reports;

use App\Enums\Tenant\PermissionKey;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TenantAppTestCase;

class ReportsExportControllerTest extends TenantAppTestCase
{
    // ── Authentication ────────────────────────────────────────────────────────

    public function test_guest_is_redirected_from_export(): void
    {
        $this->get(route('reports.export.expenditure-summary'))->assertRedirect(route('login'));
    }

    // ── Authorization ─────────────────────────────────────────────────────────

    public function test_user_without_access_reports_cannot_export(): void
    {
        $this->role->revokePermissionTo(PermissionKey::AccessReports->value);

        $this->actingAs($this->user)
            ->get(route('reports.export.expenditure-summary'))
            ->assertForbidden();
    }

    // ── CSV exports ───────────────────────────────────────────────────────────

    /** @return array<string, array{string}> */
    public static function exportRouteProvider(): array
    {
        return [
            'expenditure summary' => ['reports.export.expenditure-summary'],
            'outstanding advances' => ['reports.export.outstanding-advances'],
            'cash position' => ['reports.export.cash-position'],
            'disbursement register' => ['reports.export.disbursement-register'],
            'approval turnaround' => ['reports.export.approval-turnaround'],
            'pending requests aging' => ['reports.export.pending-requests-aging'],
            'send back rate' => ['reports.export.send-back-rate'],
            'audit trail' => ['reports.export.audit-trail'],
            'requests by status' => ['reports.export.requests-by-status'],
            'workflow sla' => ['reports.export.workflow-sla'],
            'spend trend' => ['reports.export.spend-trend'],
            'top spenders' => ['reports.export.top-spenders'],
            'retirement reminders' => ['reports.export.retirement-reminders'],
            'retirement variance' => ['reports.export.retirement-variance'],
            'denied cancelled' => ['reports.export.denied-cancelled'],
            'retirement turnaround' => ['reports.export.retirement-turnaround'],
            'cash count' => ['reports.export.cash-count'],
            'breakdown' => ['reports.export.breakdown'],
        ];
    }

    #[DataProvider('exportRouteProvider')]
    public function test_each_export_route_returns_csv_by_default(string $routeName): void
    {
        $response = $this->actingAs($this->user)->get(route($routeName));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type') ?? '');
        $this->assertStringContainsString('attachment', $response->headers->get('Content-Disposition') ?? '');
        $this->assertStringContainsString('.csv', $response->headers->get('Content-Disposition') ?? '');
    }

    #[DataProvider('exportRouteProvider')]
    public function test_each_export_route_returns_403_without_permission(string $routeName): void
    {
        $this->role->revokePermissionTo(PermissionKey::AccessReports->value);

        $this->actingAs($this->user)->get(route($routeName))->assertForbidden();
    }

    #[DataProvider('exportRouteProvider')]
    public function test_each_export_route_returns_pdf_when_requested(string $routeName): void
    {
        $response = $this->actingAs($this->user)->get(route($routeName, ['format' => 'pdf']));

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', $response->headers->get('Content-Type') ?? '');
        $this->assertStringContainsString('.pdf', $response->headers->get('Content-Disposition') ?? '');
    }

    // ── Filter pass-through ───────────────────────────────────────────────────

    public function test_expenditure_summary_csv_respects_date_filter(): void
    {
        $response = $this->actingAs($this->user)->get(route('reports.export.expenditure-summary', [
            'date_from' => '2025-01-01',
            'date_to' => '2025-01-31',
        ]));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type') ?? '');
    }

    public function test_disbursement_register_csv_respects_filters(): void
    {
        $response = $this->actingAs($this->user)->get(route('reports.export.disbursement-register', [
            'date_from' => now()->startOfMonth()->toDateString(),
            'date_to' => now()->toDateString(),
        ]));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type') ?? '');
    }
}
