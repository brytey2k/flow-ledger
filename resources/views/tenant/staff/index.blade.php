@extends('tenant.layouts.base')

@section('content')
<div class="fl-container-fixed">
    <div class="flex flex-wrap items-center justify-between gap-5 pb-7.5 lg:items-end">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono">{{ __('staff.title') }}</h1>
            <div class="flex items-center gap-2 text-sm font-normal text-secondary-foreground">
                {{ __('staff.subtitle') }}
            </div>
        </div>
        @can(App\Enums\Tenant\PermissionKey::CreateStaff->value)
            <div class="flex items-center gap-2.5">
                <a class="fl-btn fl-btn-outline" href="{{ route('staff.import') }}">
                    <x-tabler-file-arrow-left />
                    {{ __('staff.buttons.import') }}
                </a>
                <a class="fl-btn fl-btn-primary" href="{{ route('staff.create') }}">
                    <x-tabler-plus-filled />
                    {{ __('staff.add_new') }}
                </a>
            </div>
        @endcan
    </div>
</div>

<div class="fl-container-fixed">
    <div class="grid gap-5 lg:gap-7.5">
        <div class="fl-card fl-card-grid">
            <div class="fl-card-header">
                <h3 class="fl-card-title">{{ __('staff.all') }}</h3>
                <div class="flex items-center gap-2">
                    <span class="fl-badge fl-badge-sm fl-badge-outline">
                        {{ $staff->count() }} {{ Str::plural('Member', $staff->count()) }}
                    </span>
                </div>
            </div>

            @if($staff->isEmpty())
                <div class="fl-card-content flex flex-col gap-4 p-5 lg:p-7.5 lg:pt-4">
                    <div class="flex flex-col items-center justify-center py-12">
                        <x-tabler-user-square-rounded class="text-6xl text-muted-foreground mb-4" />
                        <h3 class="text-lg font-medium text-foreground mb-2">{{ __('staff.empty.heading') }}</h3>
                        <p class="text-sm text-secondary-foreground mb-4">{{ __('staff.empty.subtext') }}</p>
                        @can(App\Enums\Tenant\PermissionKey::CreateStaff->value)
                            <a href="{{ route('staff.create') }}" class="fl-btn fl-btn-primary">
                                <x-tabler-plus-filled />
                                {{ __('staff.buttons.add') }}
                            </a>
                        @endcan
                    </div>
                </div>
            @else
                <div class="fl-card-table">
                    <div class="fl-scrollable-x-auto border-b border-border">
                        <table class="fl-table fl-table-border">
                            <thead>
                                <tr>
                                    <th class="min-w-[80px]">
                                        <span class="fl-table-col">
                                            <span class="fl-table-col-label">{{ __('common.columns.id') }}</span>
                                        </span>
                                    </th>
                                    <th class="min-w-[200px]">
                                        <span class="fl-table-col">
                                            <span class="fl-table-col-label">{{ __('common.columns.name') }}</span>
                                        </span>
                                    </th>
                                    <th class="min-w-[180px]">
                                        <span class="fl-table-col">
                                            <span class="fl-table-col-label">{{ __('common.columns.department') }}</span>
                                        </span>
                                    </th>
                                    <th class="min-w-[180px]">
                                        <span class="fl-table-col">
                                            <span class="fl-table-col-label">{{ __('common.columns.position') }}</span>
                                        </span>
                                    </th>
                                    <th class="min-w-[200px]">
                                        <span class="fl-table-col">
                                            <span class="fl-table-col-label">{{ __('common.columns.email') }}</span>
                                        </span>
                                    </th>
                                    <th class="min-w-[150px]">
                                        <span class="fl-table-col">
                                            <span class="fl-table-col-label">{{ __('common.columns.phone') }}</span>
                                        </span>
                                    </th>
                                    <th class="min-w-[150px]">
                                        <span class="fl-table-col">
                                            <span class="fl-table-col-label">{{ __('common.columns.created') }}</span>
                                        </span>
                                    </th>
                                    <th class="min-w-[100px] text-center">
                                        <span class="fl-table-col">
                                            <span class="fl-table-col-label">{{ __('common.columns.actions') }}</span>
                                        </span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($staff as $member)
                                    <tr>
                                        <td>
                                            <span class="fl-badge fl-badge-sm fl-badge-primary">{{ $member->id }}</span>
                                        </td>
                                        <td>
                                            <a href="{{ route('staff.show', $member) }}" class="text-sm font-medium text-primary hover:underline">{{ $member->full_name }}</a>
                                        </td>
                                        <td>
                                            <span class="text-sm text-foreground">{{ $member->department->name }}</span>
                                        </td>
                                        <td>
                                            <span class="text-sm text-foreground">{{ $member->position->name }}</span>
                                        </td>
                                        <td>
                                            <span class="text-sm text-foreground">{{ $member->email ?? '—' }}</span>
                                        </td>
                                        <td>
                                            <span class="text-sm text-foreground">{{ $member->phone ?? '—' }}</span>
                                        </td>
                                        <td>
                                            <span class="text-sm text-foreground">{{ $member->created_at->format('M d, Y') }}</span>
                                        </td>
                                        <td class="text-center">
                                            @can(App\Enums\Tenant\PermissionKey::AccessStaff->value)
                                                <a href="{{ route('staff.edit', $member) }}"
                                                   class="fl-btn fl-btn-sm fl-btn-outline">
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
