@extends('tenant.layouts.base')

@section('content')
<div class="sgh-container-fixed">
    <div class="flex flex-wrap items-center justify-between gap-5 pb-7.5 lg:items-end">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono">{{ __('cost_codes.title') }}</h1>
            <div class="flex items-center gap-2 text-sm font-normal text-secondary-foreground">
                {{ __('cost_codes.subtitle') }}
            </div>
        </div>
        @can(App\Enums\Tenant\PermissionKey::CreateCostCode->value)
            <div class="flex items-center gap-2.5">
                <a class="sgh-btn sgh-btn-outline" href="{{ route('cost-codes.import') }}">
                    <x-tabler-file-arrow-left />
                    {{ __('cost_codes.import') }}
                </a>
                <a class="sgh-btn sgh-btn-primary" href="{{ route('cost-codes.create') }}">
                    <x-tabler-plus-filled />
                    {{ __('cost_codes.add_new') }}
                </a>
            </div>
        @endcan
    </div>
</div>

<div class="sgh-container-fixed">
    <x-index-filters :action="route('cost-codes.index')" :reset-url="route('cost-codes.index')" :filters="$filters" search-placeholder="Search cost code or name…">
        <div class="flex flex-col gap-1">
            <label for="department_id" class="sgh-form-label">{{ __('common.columns.department') }}</label>
            <select id="department_id" name="department_id" class="sgh-select w-full">
                <option value="">{{ __('common.all') }}</option>
                @foreach($departments as $department)
                    <option value="{{ $department->id }}" @selected(($filters['department_id'] ?? null) === $department->id)>{{ $department->name }}</option>
                @endforeach
            </select>
        </div>
    </x-index-filters>

    <div class="grid gap-5 lg:gap-7.5">
        <div class="sgh-card sgh-card-grid">
            <div class="sgh-card-header">
                <h3 class="sgh-card-title">{{ __('cost_codes.all') }}</h3>
                <div class="flex items-center gap-2">
                    <span class="sgh-badge sgh-badge-sm sgh-badge-outline">
                        {{ $costCodes->count() }} {{ Str::plural('Cost Code', $costCodes->count()) }}
                    </span>
                </div>
            </div>

            @if($costCodes->isEmpty())
                <div class="sgh-card-content flex flex-col gap-4 p-5 lg:p-7.5 lg:pt-4">
                    <div class="flex flex-col items-center justify-center py-12">
                        <x-tabler-book-filled class="text-6xl text-muted-foreground mb-4" />
                        <h3 class="text-lg font-medium text-foreground mb-2">{{ __('cost_codes.empty.heading') }}</h3>
                        <p class="text-sm text-secondary-foreground mb-4">{{ __('cost_codes.empty.subtext') }}</p>
                        @can(App\Enums\Tenant\PermissionKey::CreateCostCode->value)
                            <div class="flex flex-wrap items-center justify-center gap-2">
                                <a href="{{ route('cost-codes.import') }}" class="sgh-btn sgh-btn-outline">
                                    <x-tabler-file-arrow-left />
                                    {{ __('cost_codes.import') }}
                                </a>
                                <a href="{{ route('cost-codes.create') }}" class="sgh-btn sgh-btn-primary">
                                    <x-tabler-plus-filled />
                                    {{ __('cost_codes.buttons.add') }}
                                </a>
                            </div>
                        @endcan
                    </div>
                </div>
            @else
                <div class="sgh-card-table">
                    <div class="sgh-scrollable-x-auto border-b border-border">
                        <table class="sgh-table sgh-table-border">
                            <thead>
                                <tr>
                                    <th class="min-w-[80px]">
                                        <span class="sgh-table-col">
                                            <span class="sgh-table-col-label">{{ __('common.columns.id') }}</span>
                                        </span>
                                    </th>
                                    <th class="min-w-[120px]">
                                        <span class="sgh-table-col">
                                            <span class="sgh-table-col-label">{{ __('common.columns.code') }}</span>
                                        </span>
                                    </th>
                                    <th class="min-w-[200px]">
                                        <span class="sgh-table-col">
                                            <span class="sgh-table-col-label">{{ __('common.columns.name') }}</span>
                                        </span>
                                    </th>
                                    <th class="min-w-[180px]">
                                        <span class="sgh-table-col">
                                            <span class="sgh-table-col-label">{{ __('common.columns.department') }}</span>
                                        </span>
                                    </th>
                                    <th class="min-w-[150px]">
                                        <span class="sgh-table-col">
                                            <span class="sgh-table-col-label">{{ __('common.columns.created') }}</span>
                                        </span>
                                    </th>
                                    <th class="min-w-[100px] text-center">
                                        <span class="sgh-table-col">
                                            <span class="sgh-table-col-label">{{ __('common.columns.actions') }}</span>
                                        </span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($costCodes as $costCode)
                                    <tr>
                                        <td>
                                            <span class="sgh-badge sgh-badge-sm sgh-badge-primary">{{ $costCode->id }}</span>
                                        </td>
                                        <td>
                                            <span class="sgh-badge sgh-badge-sm sgh-badge-outline font-mono">{{ $costCode->code }}</span>
                                        </td>
                                        <td>
                                            <span class="text-sm font-medium leading-none text-mono">{{ $costCode->name }}</span>
                                        </td>
                                        <td>
                                            <span class="text-sm text-foreground">{{ $costCode->department?->name }}</span>
                                        </td>
                                        <td>
                                            <span class="text-sm text-foreground">{{ $costCode->created_at->format('M d, Y') }}</span>
                                        </td>
                                        <td class="text-center">
                                            @can(App\Enums\Tenant\PermissionKey::AccessCostCodes->value)
                                                <a href="{{ route('cost-codes.edit', $costCode) }}"
                                                   class="sgh-btn sgh-btn-sm sgh-btn-outline">
                                                    <x-tabler-pencil-filled />
                                                    {{ __('common.edit') }}
                                                </a>
                                            @endcan
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
