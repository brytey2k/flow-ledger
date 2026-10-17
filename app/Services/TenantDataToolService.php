<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\Landlord\TenantDataToolFilters;
use App\Enums\TenantDataTool;
use App\Models\Tenant;
use App\Models\Tenant\PaymentRequest;
use App\Models\Tenant\RetirementRequest;
use Closure;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Hard-deletes transactional tenant data for the landlord admin tools.
 *
 * Every purge is written as "children first, parents last" against subqueries on the still-present
 * parents, so a dry run (counts only) and a real run share exactly the same queries.
 */
class TenantDataToolService
{
    /** @var array<string, int> */
    private array $counts = [];

    /** @var list<string> */
    private array $filePaths = [];

    private bool $dryRun = false;

    private string|null $exportTempPath = null;

    private string|null $artifactPath = null;

    public function __construct(private readonly TenantDataExporter $exporter) {}

    public function artifactPath(): string|null
    {
        return $this->artifactPath;
    }

    /** @return array<string, int> */
    public function preview(Tenant $tenant, TenantDataTool $tool, TenantDataToolFilters $filters): array
    {
        return $this->run($tenant, $tool, $filters, true);
    }

    /** @return array<string, int> */
    public function execute(Tenant $tenant, TenantDataTool $tool, TenantDataToolFilters $filters): array
    {
        return $this->run($tenant, $tool, $filters, false);
    }

    /** @return array<string, int> */
    private function run(Tenant $tenant, TenantDataTool $tool, TenantDataToolFilters $filters, bool $dryRun): array
    {
        $this->counts = [];
        $this->filePaths = [];
        $this->exportTempPath = null;
        $this->artifactPath = null;
        $this->dryRun = $dryRun;

        /** @var array<string, int> $counts */
        $counts = $tenant->run(function () use ($tool, $filters): array {
            DB::transaction(function () use ($tool, $filters): void {
                match ($tool) {
                    TenantDataTool::ClearPaymentRequests => $this->purgePaymentRequests(
                        fn(): Builder => $this->requestsQuery('payment_requests', $filters)->select('id'),
                    ),
                    TenantDataTool::ClearRetirements => $this->purgeRetirements(
                        fn(): Builder => $this->requestsQuery('retirement_requests', $filters)->select('id'),
                        revertPaymentRequests: true,
                    ),
                    TenantDataTool::ClearCashbook => $this->clearCashbook($filters),
                    TenantDataTool::ResetInFlightWorkflows => $this->resetInFlightWorkflows($filters),
                    TenantDataTool::ClearActivityLog => $this->clearActivityLog($filters),
                    TenantDataTool::RecalculateCashbookBalances => $this->recalculateBalances(),
                    TenantDataTool::ExportData => $this->exportData(),
                };
            });

            if (! $this->dryRun) {
                $this->deleteFiles();
            }

            return $this->counts;
        });

        $this->storeExport($tenant);

        return $counts;
    }

    private function exportData(): void
    {
        if ($this->dryRun) {
            $this->counts = $this->exporter->counts();

            return;
        }

        $export = $this->exporter->export();
        $this->exportTempPath = $export['path'];
        $this->counts = $export['counts'];
    }

    /** Moves the finished export out of the tenant's storage context into the landlord's private storage. */
    private function storeExport(Tenant $tenant): void
    {
        if ($this->exportTempPath === null) {
            return;
        }

        $key = $tenant->getTenantKey();
        $path = sprintf('tenant-exports/%s/%s.zip', is_scalar($key) ? (string) $key : '', now()->format('Ymd-His'));
        $stream = fopen($this->exportTempPath, 'r');

        if ($stream !== false) {
            Storage::disk('local')->put($path, $stream);
            fclose($stream);
            $this->artifactPath = $path;
        }

        unlink($this->exportTempPath);
        $this->exportTempPath = null;
    }

    private function requestsQuery(string $table, TenantDataToolFilters $filters): Builder
    {
        return DB::table($table)
            ->when($filters->statuses !== [], fn(Builder $query) => $query->whereIn('status', $filters->statuses))
            ->when($filters->keepDrafts, fn(Builder $query) => $query->where('status', '!=', 'draft'))
            ->when($filters->from !== null, fn(Builder $query) => $query->where('created_at', '>=', $filters->from))
            ->when($filters->to !== null, fn(Builder $query) => $query->where('created_at', '<=', $filters->to));
    }

    /** @param Closure(): Builder $paymentRequestIds */
    private function purgePaymentRequests(Closure $paymentRequestIds): void
    {
        $this->purgeRetirements(
            fn(): Builder => DB::table('retirement_requests')->select('id')->whereIn('payment_request_id', $paymentRequestIds()),
            revertPaymentRequests: false,
        );

        $this->purgeRequestDependents(PaymentRequest::class, $paymentRequestIds);
        $this->delete('payment_request_items', DB::table('payment_request_items')->whereIn('payment_request_id', $paymentRequestIds()));
        $this->delete('retirement_reminder_logs', DB::table('retirement_reminder_logs')->whereIn('payment_request_id', $paymentRequestIds()));
        $this->delete('payment_requests', DB::table('payment_requests')->whereIn('id', $paymentRequestIds()));
    }

    /** @param Closure(): Builder $retirementIds */
    private function purgeRetirements(Closure $retirementIds, bool $revertPaymentRequests): void
    {
        $this->purgeRequestDependents(RetirementRequest::class, $retirementIds);
        $this->delete('retirement_request_items', DB::table('retirement_request_items')->whereIn('retirement_request_id', $retirementIds()));

        if ($revertPaymentRequests) {
            $settledPaymentRequestIds = fn(): Builder => DB::table('retirement_requests')
                ->select('payment_request_id')
                ->whereIn('id', $retirementIds())
                ->where('status', 'settled');

            $revert = DB::table('payment_requests')
                ->whereIn('id', $settledPaymentRequestIds())
                ->where('status', 'retired');
            $this->bump('payment_requests_reverted_to_disbursed', $this->dryRun ? $revert->count() : $revert->update(['status' => 'disbursed']));

            $this->delete('retirement_reminder_logs', DB::table('retirement_reminder_logs')->whereIn(
                'payment_request_id',
                DB::table('retirement_requests')->select('payment_request_id')->whereIn('id', $retirementIds()),
            ));
        }

        $this->delete('retirement_requests', DB::table('retirement_requests')->whereIn('id', $retirementIds()));
    }

    /**
     * Removes everything that hangs off a request through a morph: workflow history, comments,
     * attachments (rows and files), activity log rows and linked cashbook entries.
     *
     * @param class-string $morphClass
     * @param Closure(): Builder $ids
     */
    private function purgeRequestDependents(string $morphClass, Closure $ids): void
    {
        $this->purgeWorkflowInstances(
            fn(): Builder => DB::table('workflow_instances')->select('id')
                ->where('workflowable_type', $morphClass)
                ->whereIn('workflowable_id', $ids()),
        );

        $this->delete('comments', DB::table('comments')->where('commentable_type', $morphClass)->whereIn('commentable_id', $ids()));

        $attachments = DB::table('attachments')->where('attachable_type', $morphClass)->whereIn('attachable_id', $ids());
        $this->collectFilePaths(clone $attachments);
        $this->delete('attachments', $attachments);

        $this->deleteCashbookEntries(
            DB::table('cashbook_entries')->where('sourceable_type', $morphClass)->whereIn('sourceable_id', $ids()),
        );

        $this->delete('activity_log', DB::table('activity_log')->where('subject_type', $morphClass)->whereIn('subject_id', $ids()));
    }

    /** @param Closure(): Builder $instanceIds */
    private function purgeWorkflowInstances(Closure $instanceIds): void
    {
        $stageIds = fn(): Builder => DB::table('workflow_instance_stages')->select('id')->whereIn('workflow_instance_id', $instanceIds());
        $documentRequestIds = fn(): Builder => DB::table('workflow_document_requests')->select('id')->whereIn('workflow_instance_id', $instanceIds());

        $this->delete('workflow_review_referrals', DB::table('workflow_review_referrals')->whereIn('workflow_document_request_id', $documentRequestIds()));

        if (! $this->dryRun) {
            DB::table('attachments')->whereIn('workflow_document_request_id', $documentRequestIds())->update(['workflow_document_request_id' => null]);
            DB::table('workflow_document_requests')->whereIn('workflow_instance_id', $instanceIds())->update(['parent_document_request_id' => null]);
        }

        $this->delete('workflow_document_requests', DB::table('workflow_document_requests')->whereIn('workflow_instance_id', $instanceIds()));
        $this->delete('workflow_instance_stage_recovery_roles', DB::table('workflow_instance_stage_recovery_roles')->whereIn('workflow_instance_stage_id', $stageIds()));
        $this->delete('workflow_instance_actor_claims', DB::table('workflow_instance_actor_claims')->whereIn('workflow_instance_id', $instanceIds()));
        $this->delete('workflow_actions', DB::table('workflow_actions')->whereIn('workflow_instance_stage_id', $stageIds()));

        if (! $this->dryRun) {
            DB::table('workflow_instances')->whereIn('id', $instanceIds())->update(['cancelled_at_stage_id' => null]);
        }

        $this->delete('workflow_instance_stages', DB::table('workflow_instance_stages')->whereIn('workflow_instance_id', $instanceIds()));
        $this->delete('workflow_instances', DB::table('workflow_instances')->whereIn('id', $instanceIds()));
    }

    private function clearCashbook(TenantDataToolFilters $filters): void
    {
        $cashCountIds = fn(): Builder => DB::table('cash_counts')->select('id')
            ->when($filters->from !== null, fn(Builder $query) => $query->where('counted_at', '>=', $filters->from))
            ->when($filters->to !== null, fn(Builder $query) => $query->where('counted_at', '<=', $filters->to));

        $this->delete('cash_count_items', DB::table('cash_count_items')->whereIn('cash_count_id', $cashCountIds()));
        $this->delete('cash_counts', DB::table('cash_counts')->whereIn('id', $cashCountIds()));

        $this->deleteCashbookEntries(
            DB::table('cashbook_entries')
                ->when($filters->from !== null, fn(Builder $query) => $query->where('entry_date', '>=', $filters->from?->toDateString()))
                ->when($filters->to !== null, fn(Builder $query) => $query->where('entry_date', '<=', $filters->to?->toDateString())),
        );
    }

    private function resetInFlightWorkflows(TenantDataToolFilters $filters): void
    {
        $instanceIds = fn(): Builder => DB::table('workflow_instances')->select('id')
            ->where('status', 'in_progress')
            ->when($filters->from !== null, fn(Builder $query) => $query->where('created_at', '>=', $filters->from))
            ->when($filters->to !== null, fn(Builder $query) => $query->where('created_at', '<=', $filters->to));

        foreach (['payment_requests' => PaymentRequest::class, 'retirement_requests' => RetirementRequest::class] as $table => $morphClass) {
            $stuck = DB::table($table)
                ->whereIn('status', ['in_workflow', 'sent_back'])
                ->whereIn('id', DB::table('workflow_instances')->select('workflowable_id')
                    ->where('workflowable_type', $morphClass)
                    ->whereIn('id', $instanceIds()));
            $this->bump("{$table}_returned_to_draft", $this->dryRun ? $stuck->count() : $stuck->update(['status' => 'draft', 'submitted_at' => null]));
        }

        $this->purgeWorkflowInstances($instanceIds);
    }

    private function clearActivityLog(TenantDataToolFilters $filters): void
    {
        $this->delete('activity_log', DB::table('activity_log')
            ->when($filters->from !== null, fn(Builder $query) => $query->where('created_at', '>=', $filters->from))
            ->when($filters->to !== null, fn(Builder $query) => $query->where('created_at', '<=', $filters->to)));
    }

    private function recalculateBalances(): void
    {
        $entryNet = '(select coalesce(sum(case when e.type = \'debit\' then e.amount else -e.amount end), 0)'
            . ' from cashbook_entries e where e.cashbook_id = cashbooks.id and e.deleted_at is null)';

        $mismatched = DB::table('cashbooks')->whereRaw("balance <> {$entryNet}");

        $this->bump('cashbooks_checked', DB::table('cashbooks')->count());
        $this->bump('cashbooks_corrected', $this->dryRun ? $mismatched->count() : $mismatched->update(['balance' => DB::raw($entryNet)]));
    }

    /** Deletes cashbook entries and moves each affected cashbook balance by the net amount removed. */
    private function deleteCashbookEntries(Builder $entries): void
    {
        $nets = (clone $entries)
            ->whereNull('deleted_at')
            ->selectRaw("cashbook_id, sum(case when type = 'debit' then amount else -amount end) as net")
            ->groupBy('cashbook_id')
            ->get();

        if (! $this->dryRun) {
            foreach ($nets as $row) {
                DB::table('cashbooks')->where('id', $row->cashbook_id)->decrement('balance', is_numeric($row->net) ? (float) $row->net : 0.0);
            }
        }

        $this->delete('cashbook_entries', $entries);
    }

    private function collectFilePaths(Builder $attachments): void
    {
        /** @var list<string> $paths */
        $paths = $attachments->pluck('path')->all();
        $this->filePaths = [...$this->filePaths, ...$paths];
        $this->bump('files', count($paths));
    }

    private function deleteFiles(): void
    {
        foreach (array_chunk($this->filePaths, 500) as $chunk) {
            Storage::disk('local')->delete($chunk);
        }
    }

    private function delete(string $label, Builder $query): void
    {
        $this->bump($label, $this->dryRun ? $query->count() : $query->delete());
    }

    private function bump(string $label, int $by): void
    {
        $this->counts[$label] = ($this->counts[$label] ?? 0) + $by;
    }
}
