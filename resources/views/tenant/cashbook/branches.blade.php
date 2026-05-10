@extends('tenant.layouts.base')

@section('content')
<!-- Container -->
<div class="kt-container-fixed">
    <div class="flex flex-wrap items-center justify-between gap-5 pb-7.5 lg:items-end">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono">Cashbook</h1>
            <div class="flex items-center gap-2 text-sm font-normal text-secondary-foreground">
                Cash balances by branch
            </div>
        </div>
    </div>
</div>
<!-- End of Container -->

<!-- Branches Table -->
<div class="kt-container-fixed">
    <div class="grid gap-5 lg:gap-7.5">
        <div class="kt-card kt-card-grid">
            <div class="kt-card-header">
                <h3 class="kt-card-title">Branches</h3>
                <div class="flex items-center gap-2">
                    <span class="badge badge-sm badge-outline">{{ $branches->count() }} {{ Str::plural('Branch', $branches->count()) }}</span>
                </div>
            </div>

            @if($branches->isEmpty())
                <div class="kt-card-content flex flex-col gap-4 p-5 lg:p-7.5 lg:pt-4">
                    <div class="flex flex-col items-center justify-center py-12">
                        <i class="ki-filled ki-calculator text-6xl text-muted-foreground mb-4"></i>
                        <h3 class="text-lg font-medium text-foreground mb-2">No branches configured</h3>
                        <p class="text-sm text-secondary-foreground">Create a branch to start tracking cash.</p>
                    </div>
                </div>
            @else
                <div class="kt-card-table">
                    <div class="kt-scrollable-x-auto border-b border-border">
                        <table class="kt-table kt-table-border">
                            <thead>
                                <tr>
                                    <th class="min-w-[200px]">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">Branch</span>
                                        </span>
                                    </th>
                                    <th class="min-w-[140px]">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">Level</span>
                                        </span>
                                    </th>
                                    <th class="min-w-[160px] text-right">
                                        <span class="kt-table-col justify-end">
                                            <span class="kt-table-col-label">Balance</span>
                                        </span>
                                    </th>
                                    <th class="min-w-[80px] text-center">
                                        <span class="kt-table-col justify-center">
                                            <span class="kt-table-col-label">Actions</span>
                                        </span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($branches as $branch)
                                    <tr>
                                        <td>
                                            <span class="text-sm font-medium text-mono">{{ $branch->name }}</span>
                                        </td>
                                        <td>
                                            <span class="text-sm text-foreground">{{ $branch->level->name }}</span>
                                        </td>
                                        <td class="text-right">
                                            @if($branch->cashbook)
                                                @php $cb = $branch->cashbook; @endphp
                                                <span class="text-sm font-medium {{ (float) $cb->balance >= 0 ? 'text-success' : 'text-danger' }}">
                                                    {{ $cb->currency->symbol }} {{ number_format((float) $cb->balance, 2) }}
                                                </span>
                                            @else
                                                <span class="text-sm text-secondary-foreground">—</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <a href="{{ route('cashbook.index', $branch) }}"
                                               class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost text-primary"
                                               title="View cashbook">
                                                <i class="ki-filled ki-calculator text-lg"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
<!-- End of Branches Table -->
@endsection
