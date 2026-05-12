@extends('tenant.layouts.base')

@section('content')
<div class="kt-container-fixed">
    <div class="flex flex-wrap items-center justify-between gap-5 pb-7.5 lg:items-end">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono">{{ __('branches.title') }}</h1>
            <div class="flex items-center gap-2 text-sm font-normal text-secondary-foreground">
                {{ __('branches.subtitle') }}
            </div>
        </div>
        @can(App\Enums\Tenant\PermissionKey::CreateBranch->value)
            <a class="kt-btn kt-btn-primary" href="{{ route('branches.create') }}">
                <i class="ki-filled ki-plus"></i>
                {{ __('branches.add_new') }}
            </a>
        @endcan
    </div>
</div>

<div class="kt-container-fixed">
    <div class="grid gap-5 lg:gap-7.5">
        <div class="kt-card kt-card-grid">
            <div class="kt-card-header">
                <h3 class="kt-card-title">{{ __('branches.all') }}</h3>
                <div class="flex items-center gap-2">
                    <span class="kt-badge kt-badge-sm kt-badge-outline">
                        {{ $branches->count() }} {{ Str::plural('Branch', $branches->count()) }}
                    </span>
                </div>
            </div>

            @if($branches->isEmpty())
                <div class="kt-card-content flex flex-col gap-4 p-5 lg:p-7.5 lg:pt-4">
                    <div class="flex flex-col items-center justify-center py-12">
                        <i class="ki-filled ki-office-bag text-6xl text-muted-foreground mb-4"></i>
                        <h3 class="text-lg font-medium text-foreground mb-2">{{ __('branches.empty.heading') }}</h3>
                        <p class="text-sm text-secondary-foreground mb-4">{{ __('branches.empty.subtext') }}</p>
                        @can(App\Enums\Tenant\PermissionKey::CreateBranch->value)
                            <a href="{{ route('branches.create') }}" class="kt-btn kt-btn-primary">
                                <i class="ki-filled ki-plus"></i>
                                {{ __('branches.buttons.add') }}
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
                                    <th class="min-w-[250px]">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">{{ __('branches.columns.branch_name') }}</span>
                                        </span>
                                    </th>
                                    <th class="min-w-[120px]">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">{{ __('common.columns.code') }}</span>
                                        </span>
                                    </th>
                                    <th class="min-w-[150px]">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">{{ __('common.columns.level') }}</span>
                                        </span>
                                    </th>
                                    <th class="min-w-[150px]">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">{{ __('common.columns.parent') }}</span>
                                        </span>
                                    </th>
                                    <th class="min-w-[80px] text-center">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">{{ __('common.columns.position') }}</span>
                                        </span>
                                    </th>
                                    <th class="min-w-[100px] text-center">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">{{ __('common.columns.actions') }}</span>
                                        </span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($branches as $branch)
                                    @php
                                        $depth = $branch->depth;
                                        $hasChildren = $branch->hasChildren();
                                    @endphp
                                    <tr>
                                        <td>
                                            <div class="flex items-center gap-2">
                                                @if($depth > 0)
                                                    <i class="ki-filled ki-arrow-down-right text-muted-foreground text-sm"></i>
                                                @endif
                                                <div class="flex flex-col gap-1">
                                                    <span class="text-sm font-medium leading-none text-mono">{{ $branch->name }}</span>
                                                    @if($hasChildren)
                                                        <span class="text-2sm text-muted-foreground">
                                                            Has {{ $branch->countChildren() }} {{ Str::plural('child', $branch->countChildren()) }}
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            @if($branch->code)
                                                <span class="kt-badge kt-badge-sm kt-badge-outline">{{ $branch->code }}</span>
                                            @else
                                                <span class="text-2sm text-muted-foreground">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="kt-badge kt-badge-sm kt-badge-primary">{{ $branch->level->name }}</span>
                                        </td>
                                        <td>
                                            @if($branch->parent)
                                                <span class="text-sm text-foreground">{{ $branch->parent->name }}</span>
                                            @else
                                                <span class="text-2sm text-muted-foreground">{{ __('common.root') }}</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <span class="kt-badge kt-badge-sm">{{ $branch->position }}</span>
                                        </td>
                                        <td class="text-center">
                                            <div class="flex items-center justify-center gap-2">
                                                @can(\App\Enums\Tenant\PermissionKey::AccessCashbook->value)
                                                    <a href="{{ route('cashbook.index', $branch) }}"
                                                       class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost text-primary"
                                                       title="{{ __('navigation.cashbook') }}">
                                                        <i class="ki-filled ki-calculator text-lg"></i>
                                                    </a>
                                                @endcan
                                                @can(App\Enums\Tenant\PermissionKey::AccessBranches->value)
                                                    <a href="{{ route('branches.edit', $branch) }}"
                                                       class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost text-primary"
                                                       title="{{ __('common.edit') }}">
                                                        <i class="ki-filled ki-notepad-edit text-lg"></i>
                                                    </a>
                                                    <form action="{{ route('branches.destroy', $branch) }}" method="POST"
                                                          onsubmit="return confirm('{{ __('branches.confirm_delete_short') }}')" class="inline">
                                                        @csrf @method('DELETE')
                                                        <button type="submit"
                                                                class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost text-danger"
                                                                title="{{ __('common.delete') }}">
                                                            <i class="ki-filled ki-trash text-lg"></i>
                                                        </button>
                                                    </form>
                                                @endcan
                                            </div>
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
@endsection
