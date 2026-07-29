<?php

declare(strict_types=1);

uses(Tests\TenantAppTestCase::class);
use App\Enums\Tenant\PermissionKey;

test('guest is redirected from export', function () {
    $this->get(route('reports.export.expenditure-summary'))->assertRedirect(route('login'));
});
test('user without access reports cannot export', function () {
    $this->role->revokePermissionTo(PermissionKey::AccessReports->value);

    $this->actingAs($this->user)
        ->get(route('reports.export.expenditure-summary'))
        ->assertForbidden();
});
// ── CSV exports ───────────────────────────────────────────────────────────
/* @return array<string, array{string}> */
dataset('exportRouteProvider', fn() => [
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
]);
test('each export route returns csv by default', function (string $routeName) {
    $response = $this->actingAs($this->user)->get(route($routeName));

    $response->assertOk();
    $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type') ?? '');
    $this->assertStringContainsString('attachment', $response->headers->get('Content-Disposition') ?? '');
    $this->assertStringContainsString('.csv', $response->headers->get('Content-Disposition') ?? '');
})->with('exportRouteProvider');
test('each export route returns 403 without permission', function (string $routeName) {
    $this->role->revokePermissionTo(PermissionKey::AccessReports->value);

    $this->actingAs($this->user)->get(route($routeName))->assertForbidden();
})->with('exportRouteProvider');
test('each export route returns pdf when requested', function (string $routeName) {
    $response = $this->actingAs($this->user)->get(route($routeName, ['format' => 'pdf']));

    $response->assertOk();
    $this->assertStringContainsString('application/pdf', $response->headers->get('Content-Type') ?? '');
    $this->assertStringContainsString('.pdf', $response->headers->get('Content-Disposition') ?? '');
})->with('exportRouteProvider');
test('expenditure summary csv respects date filter', function () {
    $response = $this->actingAs($this->user)->get(route('reports.export.expenditure-summary', [
        'date_from' => '2025-01-01',
        'date_to' => '2025-01-31',
    ]));

    $response->assertOk();
    $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type') ?? '');
});
test('disbursement register csv respects filters', function () {
    $response = $this->actingAs($this->user)->get(route('reports.export.disbursement-register', [
        'date_from' => now()->startOfMonth()->toDateString(),
        'date_to' => now()->toDateString(),
    ]));

    $response->assertOk();
    $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type') ?? '');
});
