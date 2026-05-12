@extends('tenant.layouts.base')

@section('content')
<div class="kt-container-fixed">
    <div class="flex flex-wrap items-center justify-between gap-5 pb-7.5 lg:items-end">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono">{{ __('staff.title') }}</h1>
            <div class="flex items-center gap-2 text-sm font-normal text-secondary-foreground">
                {{ __('staff.subtitle') }}
            </div>
        </div>
        @can(App\Enums\Tenant\PermissionKey::CreateStaff->value)
            <a class="kt-btn kt-btn-primary" href="{{ route('staff.create') }}">
                <i class="ki-filled ki-plus"></i>
                {{ __('staff.add_new') }}
            </a>
        @endcan
    </div>
</div>

<div class="kt-container-fixed">
    <div class="grid gap-5 lg:gap-7.5">
        @if(session('success'))
            <div class="kt-alert kt-alert-success">
                <i class="ki-filled ki-check-circle"></i>
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="kt-alert kt-alert-danger">
                <i class="ki-filled ki-information"></i>
                {{ session('error') }}
            </div>
        @endif

        <div class="kt-card kt-card-grid">
            <div class="kt-card-header">
                <h3 class="kt-card-title">{{ __('staff.all') }}</h3>
                <div class="flex items-center gap-2">
                    <span class="kt-badge kt-badge-sm kt-badge-outline">
                        {{ $staff->count() }} {{ Str::plural('Member', $staff->count()) }}
                    </span>
                </div>
            </div>

            @if($staff->isEmpty())
                <div class="kt-card-content flex flex-col gap-4 p-5 lg:p-7.5 lg:pt-4">
                    <div class="flex flex-col items-center justify-center py-12">
                        <i class="ki-filled ki-profile-user text-6xl text-muted-foreground mb-4"></i>
                        <h3 class="text-lg font-medium text-foreground mb-2">{{ __('staff.empty.heading') }}</h3>
                        <p class="text-sm text-secondary-foreground mb-4">{{ __('staff.empty.subtext') }}</p>
                        @can(App\Enums\Tenant\PermissionKey::CreateStaff->value)
                            <a href="{{ route('staff.create') }}" class="kt-btn kt-btn-primary">
                                <i class="ki-filled ki-plus"></i>
                                {{ __('staff.buttons.add') }}
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
                                    <th class="min-w-[80px]">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">{{ __('common.columns.id') }}</span>
                                        </span>
                                    </th>
                                    <th class="min-w-[200px]">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">{{ __('common.columns.name') }}</span>
                                        </span>
                                    </th>
                                    <th class="min-w-[180px]">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">{{ __('common.columns.department') }}</span>
                                        </span>
                                    </th>
                                    <th class="min-w-[180px]">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">{{ __('common.columns.position') }}</span>
                                        </span>
                                    </th>
                                    <th class="min-w-[200px]">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">{{ __('common.columns.email') }}</span>
                                        </span>
                                    </th>
                                    <th class="min-w-[150px]">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">{{ __('common.columns.phone') }}</span>
                                        </span>
                                    </th>
                                    <th class="min-w-[150px]">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">{{ __('common.columns.created') }}</span>
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
                                @foreach($staff as $member)
                                    <tr>
                                        <td>
                                            <span class="kt-badge kt-badge-sm kt-badge-primary">{{ $member->id }}</span>
                                        </td>
                                        <td>
                                            <span class="text-sm font-medium leading-none text-mono">{{ $member->full_name }}</span>
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
                                                   class="kt-btn kt-btn-sm kt-btn-outline">
                                                    <i class="ki-filled ki-pencil"></i>
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
