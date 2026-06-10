@extends('tenant.layouts.base')

@section('content')
<div class="kt-container-fixed">
    <div class="flex flex-wrap items-center justify-between gap-5 pb-7.5 lg:items-end">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono">{{ $staff->full_name }}</h1>
            <div class="flex items-center gap-2 text-sm font-normal text-secondary-foreground">
                <span class="text-sm text-muted-foreground">{{ $staff->email ?? '-' }}</span>
                @if($staff->branch)
                    <span>&bull;</span>
                    <span class="text-sm text-muted-foreground">{{ $staff->branch->name }}</span>
                @endif
            </div>
        </div>
        <div class="flex items-center gap-2.5">
            @can(App\Enums\Tenant\PermissionKey::AccessStaff->value)
                <a class="kt-btn kt-btn-outline" href="{{ route('staff.edit', $staff) }}">
                    <i class="ki-filled ki-pencil"></i>
                    {{ __('staff.edit_title') }}
                </a>
            @endcan

            @can(App\Enums\Tenant\PermissionKey::DeleteStaff->value)
                <button type="button" class="kt-btn kt-btn-danger" onclick="if(confirm('{{ __('staff.confirm_delete') }}')) { document.getElementById('delete-staff-form').submit(); }">
                    <i class="ki-filled ki-trash"></i>
                    {{ __('staff.buttons.delete') }}
                </button>
            @endcan

            <a class="kt-btn kt-btn-outline" href="{{ route('staff.index') }}">
                <i class="ki-filled ki-arrow-left"></i>
                {{ __('staff.back') }}
            </a>
        </div>
    </div>
</div>

<div class="kt-container-fixed">
    <div class="kt-card">
        <div class="kt-card-header">
            <h3 class="kt-card-title">{{ __('staff.details_card') }}</h3>
        </div>
        <div class="kt-card-content grid gap-4 p-5 lg:p-7.5">
            <div>
                <p class="text-sm text-muted-foreground">{{ __('staff.fields.first_name') }}</p>
                <p class="text-lg font-medium text-mono">{{ $staff->first_name }} {{ $staff->last_name }}</p>
            </div>

            <div>
                <p class="text-sm text-muted-foreground">{{ __('staff.fields.email') }}</p>
                <p class="text-sm text-foreground">{{ $staff->email ?? '-' }}</p>
            </div>

            <div>
                <p class="text-sm text-muted-foreground">{{ __('staff.fields.phone') }}</p>
                <p class="text-sm text-foreground">{{ $staff->phone ?? '-' }}</p>
            </div>

            <div>
                <p class="text-sm text-muted-foreground">{{ __('staff.fields.department') }}</p>
                <p class="text-sm text-foreground">{{ $staff->department->name ?? '-' }}</p>
            </div>

            <div>
                <p class="text-sm text-muted-foreground">Branch</p>
                <p class="text-sm text-foreground">{{ $staff->branch->name ?? '-' }}</p>
            </div>

            <div>
                <p class="text-sm text-muted-foreground">Linked User Account</p>
                @if($staff->user)
                    <a href="{{ route('users.show', $staff->user) }}" class="text-sm font-medium text-primary hover:underline">{{ $staff->user->name }} — {{ $staff->user->email }}</a>
                @else
                    <p class="text-sm text-muted-foreground">No linked user account.</p>
                @endif
            </div>

            <div>
                <p class="text-sm text-muted-foreground">{{ __('common.columns.created') }}</p>
                <p class="text-sm text-foreground">{{ $staff->created_at->format('M d, Y g:i A') }}</p>
            </div>
        </div>
        @can(App\Enums\Tenant\PermissionKey::DeleteStaff->value)
            <form id="delete-staff-form" action="{{ route('staff.destroy', $staff) }}" method="POST" class="hidden">
                @csrf @method('DELETE')
            </form>
        @endcan
    </div>
</div>
</div>
@endsection
