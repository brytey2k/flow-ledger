@extends('tenant.layouts.base')

@section('content')
<!-- Container -->
<div class="kt-container-fixed">
    <div class="flex flex-wrap items-center justify-between gap-5 pb-7.5 lg:items-end">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono">Cashbook — {{ $branch->name }}</h1>
            <div class="flex items-center gap-2 text-sm font-normal text-secondary-foreground">
                {{ $cashbook->currency->name }} ({{ $cashbook->currency->symbol }}) cash ledger
            </div>
        </div>
        <div class="flex items-center gap-2.5">
            @can(\App\Enums\Tenant\PermissionKey::CreateCashbookEntry->value)
                <a class="kt-btn kt-btn-primary" href="{{ route('cashbook.create', $branch) }}">
                    <i class="ki-filled ki-plus"></i>
                    Add Receipt
                </a>
            @endcan
        </div>
    </div>
</div>
<!-- End of Container -->

<!-- Balance Card -->
<div class="kt-container-fixed">
    <div class="grid gap-5 lg:gap-7.5">
        <div class="kt-card">
            <div class="kt-card-header">
                <h3 class="kt-card-title">Current Balance</h3>
                <div class="flex items-center gap-2">
                    <span class="badge badge-sm badge-outline text-lg font-semibold">
                        {{ $cashbook->currency->symbol }} {{ number_format((float) $cashbook->balance, 2) }}
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- End of Balance Card -->

<!-- Entries Table -->
<div class="kt-container-fixed mt-5">
    <div class="grid gap-5 lg:gap-7.5">
        <div class="kt-card kt-card-grid">
            <div class="kt-card-header">
                <h3 class="kt-card-title">Entries</h3>
                <div class="flex items-center gap-2">
                    <span class="badge badge-sm badge-outline">
                        {{ $entries->total() }} {{ Str::plural('Entry', $entries->total()) }}
                    </span>
                </div>
            </div>

            @if($entries->isEmpty())
                <div class="kt-card-content flex flex-col gap-4 p-5 lg:p-7.5 lg:pt-4">
                    <div class="flex flex-col items-center justify-center py-12">
                        <i class="ki-filled ki-dollar text-6xl text-muted-foreground mb-4"></i>
                        <h3 class="text-lg font-medium text-foreground mb-2">No entries yet</h3>
                        <p class="text-sm text-secondary-foreground mb-4">Entries are created automatically when payments are disbursed or retirements are settled.</p>
                        @can(\App\Enums\Tenant\PermissionKey::CreateCashbookEntry->value)
                            <a href="{{ route('cashbook.create', $branch) }}" class="kt-btn kt-btn-primary">
                                <i class="ki-filled ki-plus"></i>
                                Add Manual Receipt
                            </a>
                        @endcan
                    </div>
                </div>
            @else
                <div class="kt-card-table">
                    <div class="kt-scrollable-x-auto border-b border-border">
                        <table class="kt-table kt-table-border">
                            <thead>
                                <tr>
                                    <th class="min-w-[130px]">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">Date</span>
                                        </span>
                                    </th>
                                    <th class="min-w-[250px]">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">Description</span>
                                        </span>
                                    </th>
                                    <th class="min-w-[140px]">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">Reference</span>
                                        </span>
                                    </th>
                                    <th class="min-w-[100px] text-center">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">Type</span>
                                        </span>
                                    </th>
                                    <th class="min-w-[140px] text-right">
                                        <span class="kt-table-col justify-end">
                                            <span class="kt-table-col-label">Amount</span>
                                        </span>
                                    </th>
                                    <th class="min-w-[80px] text-center">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">Actions</span>
                                        </span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($entries as $entry)
                                    <tr>
                                        <td>
                                            <span class="text-sm text-foreground">{{ $entry->entry_date->format('M d, Y') }}</span>
                                        </td>
                                        <td>
                                            <div class="flex flex-col gap-0.5">
                                                <span class="text-sm font-medium text-mono">{{ $entry->description }}</span>
                                                @if($entry->notes)
                                                    <span class="text-2sm text-secondary-foreground">{{ Str::limit($entry->notes, 60) }}</span>
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            <span class="text-sm text-foreground">{{ $entry->reference ?? '—' }}</span>
                                        </td>
                                        <td class="text-center">
                                            @if($entry->type === 'debit')
                                                <span class="badge badge-sm kt-badge kt-badge-success">Debit</span>
                                            @else
                                                <span class="badge badge-sm kt-badge kt-badge-danger">Credit</span>
                                            @endif
                                        </td>
                                        <td class="text-right">
                                            <span class="text-sm font-medium {{ $entry->type === 'debit' ? 'text-success' : 'text-danger' }}">
                                                {{ $cashbook->currency->symbol }} {{ number_format((float) $entry->amount, 2) }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            @if($entry->isManual())
                                                @can(\App\Enums\Tenant\PermissionKey::DeleteCashbookEntry->value)
                                                    <form action="{{ route('cashbook.destroy', [$branch, $entry]) }}" method="POST"
                                                          onsubmit="return confirm('Delete this receipt?');" class="inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost text-danger" title="Delete">
                                                            <i class="ki-filled ki-trash text-lg"></i>
                                                        </button>
                                                    </form>
                                                @endcan
                                            @else
                                                <span class="text-xs text-secondary-foreground">Auto</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @if($entries->hasPages())
                    <div class="kt-card-footer p-5">
                        {{ $entries->links() }}
                    </div>
                @endif
            @endif
        </div>
    </div>
</div>
<!-- End of Entries Table -->
@endsection
