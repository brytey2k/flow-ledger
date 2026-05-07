@extends('tenant.layouts.base')

@section('content')
<div class="kt-container-fixed">
    <div class="flex flex-wrap items-center justify-between gap-5 pb-7.5 lg:items-end">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono">Departments Management</h1>
            <div class="flex items-center gap-2 text-sm font-normal text-secondary-foreground">
                Manage organisational departments
            </div>
        </div>
        @can(App\Enums\Tenant\PermissionKey::CreateDepartment->value)
            <a class="kt-btn kt-btn-primary" href="{{ route('departments.create') }}">
                <i class="ki-filled ki-plus"></i>
                Add New Department
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
                <h3 class="kt-card-title">All Departments</h3>
                <div class="flex items-center gap-2">
                    <span class="kt-badge kt-badge-sm kt-badge-outline">
                        {{ $departments->count() }} {{ Str::plural('Department', $departments->count()) }}
                    </span>
                </div>
            </div>

            @if($departments->isEmpty())
                <div class="kt-card-content flex flex-col gap-4 p-5 lg:p-7.5 lg:pt-4">
                    <div class="flex flex-col items-center justify-center py-12">
                        <i class="ki-filled ki-people text-6xl text-muted-foreground mb-4"></i>
                        <h3 class="text-lg font-medium text-foreground mb-2">No departments found</h3>
                        <p class="text-sm text-secondary-foreground mb-4">Get started by creating your first department</p>
                        @can(App\Enums\Tenant\PermissionKey::CreateDepartment->value)
                            <a href="{{ route('departments.create') }}" class="kt-btn kt-btn-primary">
                                <i class="ki-filled ki-plus"></i>
                                Add Department
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
                                    <th class="min-w-[200px]">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">Department Name</span>
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
                                @foreach($departments as $department)
                                    <tr>
                                        <td>
                                            <span class="kt-badge kt-badge-sm kt-badge-primary">{{ $department->id }}</span>
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
