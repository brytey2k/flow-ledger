@extends('tenant.layouts.base')

@section('content')
<div class="sgh-container-fixed">
    <div class="flex flex-wrap items-center justify-between gap-5 pb-7.5 lg:items-end">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono">{{ __('departments.title') }}</h1>
            <div class="flex items-center gap-2 text-sm font-normal text-secondary-foreground">
                {{ __('departments.subtitle') }}
            </div>
        </div>
        <div class="flex flex-wrap items-center gap-2.5">
            @can(App\Enums\Tenant\PermissionKey::CreateDepartment->value)
                <a class="sgh-btn sgh-btn-outline" href="{{ route('departments.import') }}">
                    <x-tabler-file-arrow-left />
                    {{ __('departments.import') }}
                </a>
                <a class="sgh-btn sgh-btn-primary" href="{{ route('departments.create') }}">
                    <x-tabler-plus-filled />
                    {{ __('departments.add_new') }}
                </a>
            @endcan
        </div>
    </div>
</div>

<div class="sgh-container-fixed">
    <x-index-filters :action="route('departments.index')" :reset-url="route('departments.index')" :filters="$filters" search-placeholder="Search departments…" />

    <div class="grid gap-5 lg:gap-7.5">
        <div class="sgh-card sgh-card-grid">
            <div class="sgh-card-header">
                <h3 class="sgh-card-title">{{ __('departments.all') }}</h3>
                <div class="flex items-center gap-2">
                    <span class="sgh-badge sgh-badge-sm sgh-badge-outline">
                        {{ $departments->count() }} {{ Str::plural('Department', $departments->count()) }}
                    </span>
                </div>
            </div>

            @if($departments->isEmpty())
                <div class="sgh-card-content flex flex-col gap-4 p-5 lg:p-7.5 lg:pt-4">
                    <div class="flex flex-col items-center justify-center py-12">
                        <x-tabler-users-group class="text-6xl text-muted-foreground mb-4" />
                        <h3 class="text-lg font-medium text-foreground mb-2">{{ __('departments.empty.heading') }}</h3>
                        <p class="text-sm text-secondary-foreground mb-4">{{ __('departments.empty.subtext') }}</p>
                        @can(App\Enums\Tenant\PermissionKey::CreateDepartment->value)
                            <div class="flex flex-wrap items-center justify-center gap-2">
                                <a href="{{ route('departments.import') }}" class="sgh-btn sgh-btn-outline">
                                    <x-tabler-file-arrow-left />
                                    {{ __('departments.import') }}
                                </a>
                                <a href="{{ route('departments.create') }}" class="sgh-btn sgh-btn-primary">
                                    <x-tabler-plus-filled />
                                    {{ __('departments.buttons.add') }}
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
                                    <th class="min-w-[200px]">
                                        <span class="sgh-table-col">
                                            <span class="sgh-table-col-label">{{ __('departments.fields.name') }}</span>
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
                                @foreach($departments as $department)
                                    <tr>
                                        <td>
                                            <span class="sgh-badge sgh-badge-sm sgh-badge-primary">{{ $department->id }}</span>
                                        </td>
                                        <td>
                                            <span class="text-sm font-medium leading-none text-mono">{{ $department->name }}</span>
                                        </td>
                                        <td>
                                            <div class="flex flex-col gap-0.5">
                                                <span class="text-sm text-foreground">{{ $department->created_at->format('M d, Y') }}</span>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            @can(App\Enums\Tenant\PermissionKey::AccessDepartments->value)
                                                <a href="{{ route('departments.edit', $department) }}"
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
