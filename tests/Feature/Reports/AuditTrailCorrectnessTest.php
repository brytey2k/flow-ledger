<?php

declare(strict_types=1);

namespace Tests\Feature\Reports;

use App\Models\Role;
use App\Models\Tenant\PaymentRequest;
use App\Models\Tenant\User;
use App\Models\Tenant\WorkflowAction;
use App\Models\Tenant\WorkflowStage;
use App\Models\Tenant\WorkflowTemplate;
use App\Services\WorkflowEngineService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TenantAppTestCase;

class AuditTrailCorrectnessTest extends TenantAppTestCase
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
    }

    /** @return array<string, array{string, string}> */
    public static function decisions(): array
    {
        return [
            'approval' => ['approve', 'Approved'],
            'rejection' => ['reject', 'Rejected'],
            'return for correction' => ['send_back', 'Sent Back'],
        ];
    }

    /** @return array<string, array{string, string}> */
    public static function dateRanges(): array
    {
        return [
            'single day' => ['2026-10-07', '2026-10-07'],
            'multiple days' => ['2026-10-05', '2026-10-07'],
        ];
    }

    #[DataProvider('decisions')]
    public function test_report_offers_filters_for_real_workflow_decisions(string $decision, string $label): void
    {
        $actions = $this->createAllDecisions('2026-10-07 12:00:00');

        $response = $this->actingAs($this->user)
            ->get(route('reports.audit-trail', [
                'date_from' => '2026-10-06',
                'date_to' => '2026-10-08',
                'action' => $decision,
            ]))
            ->assertOk()
            ->assertSee('value="' . $decision . '" selected', false)
            ->assertSeeText($label);

        $this->assertSame([$actions[$decision]->id], $response->viewData('actions')->getCollection()->modelKeys());
        $response->assertDontSee('value="approved"', false)
            ->assertDontSee('value="sent_back"', false)
            ->assertDontSee('value="cancelled"', false);
    }

    #[DataProvider('decisions')]
    public function test_csv_filters_real_decisions_and_includes_late_end_date_actions(string $decision, string $label): void
    {
        $actions = $this->createAllDecisions('2026-10-07 23:59:59');
        $expected = $actions[$decision];

        $response = $this->actingAs($this->user)
            ->get(route('reports.export.audit-trail', [
                'date_from' => '2026-10-07',
                'date_to' => '2026-10-07',
                'action' => $decision,
            ]))
            ->assertOk()
            ->assertHeaderContains('Content-Type', 'text/csv');

        $rows = $this->csvRows($response->streamedContent());
        $this->assertCount(2, $rows, $label . ' should export exactly one matching decision and a header.');
        $this->assertSame($decision, $rows[1][2]);
        $this->assertSame((string) $expected->instanceStage->instance->workflowable_id, $rows[1][3]);
        $this->assertSame('2026-10-07 23:59', $rows[1][0]);
    }

    #[DataProvider('decisions')]
    public function test_pdf_receives_filtered_end_date_decisions(string $decision, string $label): void
    {
        $actions = $this->createAllDecisions('2026-10-07 23:59:59');
        $expected = $actions[$decision];

        Pdf::shouldReceive('loadView')->once()
            ->withArgs(function (string $view, array $data) use ($decision, $expected, $label): bool {
                $this->assertSame('tenant.reports.exports.generic', $view);
                $rows = $data['exportRows']->values()->all();
                $this->assertCount(1, $rows, $label . ' PDF should receive exactly one matching decision.');
                $this->assertSame($decision, $rows[0][2]);
                $this->assertSame($expected->instanceStage->instance->workflowable_id, $rows[0][3]);
                $this->assertSame('2026-10-07 23:59', $rows[0][0]);

                return true;
            })->andReturnSelf();
        Pdf::shouldReceive('setPaper')->once()->with('a4', 'landscape')->andReturnSelf();
        Pdf::shouldReceive('download')->once()->andReturn(response('PDF rendering is covered by the export tests.'));

        $this->actingAs($this->user)
            ->get(route('reports.export.audit-trail', [
                'date_from' => '2026-10-07',
                'date_to' => '2026-10-07',
                'action' => $decision,
                'format' => 'pdf',
            ]))
            ->assertOk();
    }

    #[DataProvider('dateRanges')]
    public function test_report_includes_full_selected_days_and_excludes_adjacent_days(string $dateFrom, string $dateTo): void
    {
        $expected = $this->createBoundaryDecisions($dateFrom, $dateTo);

        $response = $this->actingAs($this->user)
            ->get(route('reports.audit-trail', ['date_from' => $dateFrom, 'date_to' => $dateTo]))
            ->assertOk();

        $this->assertEqualsCanonicalizing($expected, $response->viewData('actions')->getCollection()->modelKeys());
        $this->assertSame(3, $response->viewData('actions')->total());
    }

    #[DataProvider('dateRanges')]
    public function test_csv_includes_full_selected_days_and_excludes_adjacent_days(string $dateFrom, string $dateTo): void
    {
        $expected = $this->createBoundaryDecisions($dateFrom, $dateTo);
        $expectedRequests = WorkflowAction::whereIn('id', $expected)->with('instanceStage.instance')->get()
            ->map(fn(WorkflowAction $action): string => (string) $action->instanceStage->instance->workflowable_id)->all();

        $response = $this->actingAs($this->user)
            ->get(route('reports.export.audit-trail', ['date_from' => $dateFrom, 'date_to' => $dateTo]))
            ->assertOk();

        $rows = $this->csvRows($response->streamedContent());
        $this->assertCount(4, $rows);
        $this->assertEqualsCanonicalizing($expectedRequests, array_column(array_slice($rows, 1), 3));
    }

    public function test_default_report_range_includes_actions_later_today(): void
    {
        $expected = $this->createDecision('approve', '2026-10-07 18:30:00');
        $this->travelTo(Carbon::parse('2026-10-07 19:00:00'));

        $response = $this->actingAs($this->user)->get(route('reports.audit-trail'))->assertOk();

        $this->assertSame([$expected->id], $response->viewData('actions')->getCollection()->modelKeys());
    }

    public function test_populated_pdf_export_renders_successfully(): void
    {
        $this->createDecision('approve', '2026-10-07 23:59:59');

        $response = $this->actingAs($this->user)
            ->get(route('reports.export.audit-trail', [
                'date_from' => '2026-10-07',
                'date_to' => '2026-10-07',
                'action' => 'approve',
                'format' => 'pdf',
            ]))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertStringStartsWith('%PDF-', $response->getContent());
    }

    /** @return array<string, WorkflowAction> */
    private function createAllDecisions(string $timestamp): array
    {
        return [
            'approve' => $this->createDecision('approve', $timestamp),
            'reject' => $this->createDecision('reject', $timestamp),
            'send_back' => $this->createDecision('send_back', $timestamp),
        ];
    }

    /** @return array<int, int> */
    private function createBoundaryDecisions(string $dateFrom, string $dateTo): array
    {
        $start = Carbon::parse($dateFrom)->startOfDay();
        $end = Carbon::parse($dateTo)->startOfDay();
        $this->createDecision('approve', $start->copy()->subSecond()->toDateTimeString());
        $expected = [
            $this->createDecision('approve', $start->toDateTimeString())->id,
            $this->createDecision('approve', $end->copy()->addHours(12)->toDateTimeString())->id,
            $this->createDecision('approve', $end->copy()->addDay()->subSecond()->toDateTimeString())->id,
        ];
        $this->createDecision('approve', $end->copy()->addDay()->toDateTimeString());

        return $expected;
    }

    private function createDecision(string $decision, string $timestamp): WorkflowAction
    {
        $template = WorkflowTemplate::factory()->advance()->create();
        $stage = WorkflowStage::factory()->create([
            'workflow_template_id' => $template->id,
            'display_order' => 1,
            'allow_send_back' => true,
        ]);
        $role = Role::create(['name' => 'audit_approver_' . Str::uuid(), 'guard_name' => 'web']);
        $approver = User::factory()->create();
        $approver->assignRole($role);
        $stage->roles()->sync([$role->id]);
        $request = PaymentRequest::factory()->advance()->inWorkflow()->create([
            'branch_id' => $this->branch->id,
            'currency_id' => $this->branch->currency_id,
            'total_amount' => '100.00',
        ]);

        $engine = app(WorkflowEngineService::class);
        $instance = $engine->startWorkflow($request, $template, $this->user);
        $instanceStage = $instance->instanceStages()->sole();
        $this->assertSame('active', $instanceStage->status);

        match ($decision) {
            'approve' => $engine->approve($instanceStage, $approver, 'Audit approval evidence'),
            'reject' => $engine->reject($instanceStage, $approver, 'Audit rejection evidence'),
            'send_back' => $engine->sendBack($instanceStage, $approver, 'Audit correction evidence'),
        };

        $action = $instanceStage->actions()->sole();
        $this->assertSame($decision, $action->action);
        $action->forceFill(['created_at' => $timestamp])->save();

        return $action->refresh();
    }

    /** @return array<int, array<int, string|null>> */
    private function csvRows(string $csv): array
    {
        return array_map(
            fn(string $line): array => str_getcsv($line, ',', '"', ''),
            explode("\n", trim($csv)),
        );
    }
}
