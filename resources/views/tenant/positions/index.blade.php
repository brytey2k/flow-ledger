@extends('tenant.layouts.base')

@section('content')
<div class="sgh-container-fixed">
    <div class="flex flex-wrap items-center justify-between gap-5 pb-7.5 lg:items-end">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono">{{ __('positions.title') }}</h1>
            <div class="flex items-center gap-2 text-sm font-normal text-secondary-foreground">
                {{ __('positions.subtitle') }}
            </div>
        </div>
        <div class="flex flex-wrap items-center gap-2.5">
            @can(App\Enums\Tenant\PermissionKey::CreatePosition->value)
                <a class="sgh-btn sgh-btn-outline" href="{{ route('positions.import') }}">
                    <x-tabler-file-arrow-left />
                    {{ __('positions.import') }}
                </a>
                <a class="sgh-btn sgh-btn-primary" href="{{ route('positions.create') }}">
                    <x-tabler-plus-filled />
                    {{ __('positions.add_new') }}
                </a>
            @endcan
        </div>
    </div>
</div>

<div class="sgh-container-fixed">
    <x-index-filters :action="route('positions.index')" :reset-url="route('positions.index')" :filters="$filters" search-placeholder="Search positions…" />

    <div class="grid gap-5 lg:gap-7.5">
        <div class="sgh-card sgh-card-grid">
            <div class="sgh-card-header">
                <h3 class="sgh-card-title">{{ __('positions.all') }}</h3>
                <div class="flex items-center gap-2">
                    <span class="sgh-badge sgh-badge-sm sgh-badge-outline">
                        {{ $positions->count() }} {{ Str::plural('Position', $positions->count()) }}
                    </span>
                </div>
            </div>

            @if($positions->isEmpty())
                <div class="sgh-card-content flex flex-col gap-4 p-5 lg:p-7.5 lg:pt-4">
                    <div class="flex flex-col items-center justify-center py-12">
                        <x-tabler-briefcase-filled class="text-6xl text-muted-foreground mb-4" />
                        <h3 class="text-lg font-medium text-foreground mb-2">{{ __('positions.empty.heading') }}</h3>
                        <p class="text-sm text-secondary-foreground mb-4">{{ __('positions.empty.subtext') }}</p>
                        @can(App\Enums\Tenant\PermissionKey::CreatePosition->value)
                            <div class="flex flex-wrap items-center justify-center gap-2">
                                <a href="{{ route('positions.import') }}" class="sgh-btn sgh-btn-outline">
                                    <x-tabler-file-arrow-left />
                                    {{ __('positions.import') }}
                                </a>
                                <a href="{{ route('positions.create') }}" class="sgh-btn sgh-btn-primary">
                                    <x-tabler-plus-filled />
                                    {{ __('positions.buttons.add') }}
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
                                    <th class="min-w-[250px]">
                                        <span class="sgh-table-col">
                                            <span class="sgh-table-col-label">{{ __('positions.fields.name') }}</span>
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
                                @foreach($positions as $position)
                                    <tr>
                                        <td>
                                            <span class="sgh-badge sgh-badge-sm sgh-badge-primary">{{ $position->id }}</span>
                                        </td>
                                        <td>
                                            <span class="text-sm font-medium leading-none text-mono">{{ $position->name }}</span>
                                        </td>
                                        <td>
                                            <span class="text-sm text-foreground">{{ $position->created_at->format('M d, Y') }}</span>
                                        </td>
                                        <td class="text-center">
                                            @can(App\Enums\Tenant\PermissionKey::AccessPositions->value)
                                                <a href="{{ route('positions.edit', $position) }}"
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
