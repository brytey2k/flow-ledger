<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\ManualReceiptStoreRequest;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Cashbook;
use App\Models\Tenant\CashbookEntry;
use App\Repositories\BranchRepository;
use App\Repositories\CashbookRepository;
use App\Services\BranchScopeService;
use App\Services\CashbookService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CashbookController extends Controller
{
    public function __construct(
        private readonly CashbookRepository $repository,
        private readonly BranchRepository $branchRepository,
        private readonly CashbookService $service,
        private readonly BranchScopeService $branchScope,
    ) {}

    public function branches(Request $request): View
    {
        /** @var \App\Models\Tenant\User $user */
        $user = $request->user();
        $allowedBranchIds = $this->branchScope->allowedBranchIds($user);
        $branches = $this->branchRepository->allWithCashbook()->filter(
            fn(Branch $branch) => in_array($branch->id, $allowedBranchIds, true),
        );

        return view('tenant.cashbook.branches', compact('branches'));
    }

    public function index(Branch $branch, Request $request): View
    {
        /** @var \App\Models\Tenant\User $user */
        $user = $request->user();
        abort_unless(in_array($branch->id, $this->branchScope->allowedBranchIds($user), true), 403);

        $cashbook = Cashbook::firstOrCreate(
            ['branch_id' => $branch->id],
            ['currency_id' => $branch->currency_id, 'balance' => 0],
        );

        /** @var array{type?: string, date_from?: string, date_to?: string, description?: string, amount_min?: string, amount_max?: string} $filters */
        $filters = $request->only(['type', 'date_from', 'date_to', 'description', 'amount_min', 'amount_max']);
        $entries = $this->repository->paginatedEntriesForCashbook($cashbook, $filters);

        return view('tenant.cashbook.index', compact('branch', 'cashbook', 'entries', 'filters'));
    }

    public function create(Branch $branch, Request $request): View
    {
        /** @var \App\Models\Tenant\User $user */
        $user = $request->user();
        abort_unless(in_array($branch->id, $this->branchScope->allowedBranchIds($user), true), 403);

        $cashbook = Cashbook::firstOrCreate(
            ['branch_id' => $branch->id],
            ['currency_id' => $branch->currency_id, 'balance' => 0],
        );

        return view('tenant.cashbook.create', compact('branch', 'cashbook'));
    }

    public function store(ManualReceiptStoreRequest $request, Branch $branch): RedirectResponse
    {
        /** @var \App\Models\Tenant\User $storeUser */
        $storeUser = $request->user();
        abort_unless(in_array($branch->id, $this->branchScope->allowedBranchIds($storeUser), true), 403);

        $cashbook = Cashbook::firstOrCreate(
            ['branch_id' => $branch->id],
            ['currency_id' => $branch->currency_id, 'balance' => 0],
        );

        $this->service->recordManualReceipt($cashbook, $request->toDto(), $storeUser);

        return redirect()
            ->route('cashbook.index', $branch)
            ->with('success', __('flash.cashbook.receipt_recorded'));
    }

    public function export(Branch $branch, Request $request): StreamedResponse
    {
        /** @var \App\Models\Tenant\User $user */
        $user = $request->user();
        abort_unless(in_array($branch->id, $this->branchScope->allowedBranchIds($user), true), 403);

        $cashbook = Cashbook::firstOrCreate(
            ['branch_id' => $branch->id],
            ['currency_id' => $branch->currency_id, 'balance' => 0],
        );

        /** @var array{type?: string, date_from?: string, date_to?: string, description?: string, amount_min?: string, amount_max?: string} $filters */
        $filters = $request->only(['type', 'date_from', 'date_to', 'description', 'amount_min', 'amount_max']);
        $entries = $this->repository->entriesForCashbook($cashbook, $filters);
        $symbol = $cashbook->currency->symbol;
        $filename = 'cashbook-' . str($branch->name)->slug() . '-' . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($entries, $symbol): void {
            $handle = fopen('php://output', 'w');
            assert($handle !== false);
            fputcsv($handle, ['Date', 'Description', 'Reference', 'Type', 'Amount (' . $symbol . ')', 'Notes']);
            foreach ($entries as $entry) {
                fputcsv($handle, [
                    $entry->entry_date->format('Y-m-d'),
                    $entry->description,
                    $entry->reference ?? '',
                    $entry->type,
                    number_format((float) $entry->amount, 2, '.', ''),
                    $entry->notes ?? '',
                ]);
            }
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function destroy(Branch $branch, CashbookEntry $entry, Request $request): RedirectResponse
    {
        /** @var \App\Models\Tenant\User $user */
        $user = $request->user();
        abort_unless(in_array($branch->id, $this->branchScope->allowedBranchIds($user), true), 403);

        if ($entry->isAutoGenerated()) {
            return redirect()
                ->route('cashbook.index', $branch)
                ->with('error', __('flash.cashbook.auto_entry_delete_forbidden'));
        }

        $this->service->deleteManualReceipt($entry);

        return redirect()
            ->route('cashbook.index', $branch)
            ->with('success', __('flash.cashbook.receipt_deleted'));
    }
}
