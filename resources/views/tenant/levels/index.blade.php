@extends('tenant.layouts.base')

@section('content')
<div class="sgh-container-fixed">
    <div class="flex flex-wrap items-center justify-between gap-5 pb-7.5 lg:items-end">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono">{{ __('levels.title') }}</h1>
            <div class="flex items-center gap-2 text-sm font-normal text-secondary-foreground">
                {{ __('levels.subtitle') }}
            </div>
        </div>
        @can(App\Enums\Tenant\PermissionKey::CreateLevel->value)
            <a class="sgh-btn sgh-btn-primary" href="{{ route('levels.create') }}">
                <x-tabler-plus-filled />
                {{ __('levels.add_new') }}
            </a>
        @endcan
    </div>
</div>

<div class="sgh-container-fixed">
    <x-index-filters :action="route('levels.index')" :reset-url="route('levels.index')" :filters="$filters" search-placeholder="Search levels…" />

    <div class="grid gap-5 lg:gap-7.5">
        <div class="sgh-card sgh-card-grid">
            <div class="sgh-card-header">
                <h3 class="sgh-card-title">{{ __('levels.all') }}</h3>
                <div class="flex items-center gap-2">
                    <span class="sgh-badge sgh-badge-sm sgh-badge-outline">
                        {{ $levels->count() }} {{ Str::plural('Level', $levels->count()) }}
                    </span>
                </div>
            </div>

            @if($levels->isEmpty())
                <div class="sgh-card-content flex flex-col gap-4 p-5 lg:p-7.5 lg:pt-4">
                    <div class="flex flex-col items-center justify-center py-12">
                        <x-tabler-stack class="text-6xl text-muted-foreground mb-4" />
                        <h3 class="text-lg font-medium text-foreground mb-2">{{ __('levels.empty.heading') }}</h3>
                        <p class="text-sm text-secondary-foreground mb-4">{{ __('levels.empty.subtext') }}</p>
                        @can(App\Enums\Tenant\PermissionKey::CreateLevel->value)
                            <a href="{{ route('levels.create') }}" class="sgh-btn sgh-btn-primary">
                                <x-tabler-plus-filled />
                                {{ __('levels.buttons.add') }}
                            </a>
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
                                            <span class="sgh-table-col-label">{{ __('common.columns.position') }}</span>
                                        </span>
                                    </th>
                                    <th class="min-w-[200px]">
                                        <span class="sgh-table-col">
                                            <span class="sgh-table-col-label">{{ __('levels.fields.name') }}</span>
                                        </span>
                                    </th>
                                    <th class="min-w-[120px]">
                                        <span class="sgh-table-col">
                                            <span class="sgh-table-col-label">{{ __('levels.columns.branches') }}</span>
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
                                @foreach($levels as $level)
                                    <tr>
                                        <td>
                                            <span class="sgh-badge sgh-badge-sm sgh-badge-primary">{{ $level->position }}</span>
                                        </td>
                                        <td>
                                            <span class="text-sm font-medium leading-none text-mono">{{ $level->name }}</span>
                                        </td>
                                        <td>
                                            <span class="text-sm text-foreground">{{ $level->branches_count ?? $level->branches()->count() }}</span>
                                        </td>
                                        <td>
                                            <div class="flex flex-col gap-0.5">
                                                <span class="text-sm text-foreground">{{ $level->created_at->format('M d, Y') }}</span>
                                                <span class="text-2sm text-secondary-foreground">{{ $level->created_at->format('g:i A') }}</span>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="flex items-center justify-center gap-2">
                                                @can(App\Enums\Tenant\PermissionKey::AccessLevels->value)
                                                    <a href="{{ route('levels.edit', $level) }}"
                                                       class="sgh-btn sgh-btn-sm sgh-btn-icon sgh-btn-ghost text-primary"
                                                       title="{{ __('common.edit') }}">
                                                        <x-tabler-edit-filled class="text-lg" />
                                                    </a>
                                                    <form action="{{ route('levels.destroy', $level) }}" method="POST"
                                                          onsubmit="return confirm('{{ __('levels.confirm_delete_short') }}')" class="inline">
                                                        @csrf @method('DELETE')
                                                        <button type="submit"
                                                                class="sgh-btn sgh-btn-sm sgh-btn-icon sgh-btn-ghost text-danger"
                                                                title="{{ __('common.delete') }}">
                                                            <x-tabler-trash-filled class="text-lg" />
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
