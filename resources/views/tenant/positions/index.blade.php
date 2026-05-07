@extends('tenant.layouts.base')

@section('content')
<div class="kt-container-fixed">
    <div class="flex flex-wrap items-center justify-between gap-5 pb-7.5 lg:items-end">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono">Positions</h1>
            <div class="flex items-center gap-2 text-sm font-normal text-secondary-foreground">
                Manage staff positions in your organisation
            </div>
        </div>
        @can(App\Enums\Tenant\PermissionKey::CreatePosition->value)
            <a class="kt-btn kt-btn-primary" href="{{ route('positions.create') }}">
                <i class="ki-filled ki-plus"></i>
                Add New Position
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
                <h3 class="kt-card-title">All Positions</h3>
                <div class="flex items-center gap-2">
                    <span class="kt-badge kt-badge-sm kt-badge-outline">
                        {{ $positions->count() }} {{ Str::plural('Position', $positions->count()) }}
                    </span>
                </div>
            </div>

            @if($positions->isEmpty())
                <div class="kt-card-content flex flex-col gap-4 p-5 lg:p-7.5 lg:pt-4">
                    <div class="flex flex-col items-center justify-center py-12">
                        <i class="ki-filled ki-briefcase text-6xl text-muted-foreground mb-4"></i>
                        <h3 class="text-lg font-medium text-foreground mb-2">No positions found</h3>
                        <p class="text-sm text-secondary-foreground mb-4">Get started by creating your first position</p>
                        @can(App\Enums\Tenant\PermissionKey::CreatePosition->value)
                            <a href="{{ route('positions.create') }}" class="kt-btn kt-btn-primary">
                                <i class="ki-filled ki-plus"></i>
                                Add Position
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
                                            <span class="kt-table-col-label">ID</span>
                                        </span>
                                    </th>
                                    <th class="min-w-[250px]">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">Position Name</span>
                                        </span>
                                    </th>
                                    <th class="min-w-[150px]">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">Created</span>
                                        </span>
                                    </th>
                                    <th class="min-w-[100px] text-center">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">Actions</span>
                                        </span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($positions as $position)
                                    <tr>
                                        <td>
                                            <span class="kt-badge kt-badge-sm kt-badge-primary">{{ $position->id }}</span>
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
                                                   class="kt-btn kt-btn-sm kt-btn-outline">
                                                    <i class="ki-filled ki-pencil"></i>
                                                    Edit
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
