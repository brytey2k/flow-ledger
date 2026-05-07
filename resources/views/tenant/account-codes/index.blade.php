@extends('tenant.layouts.base')

@section('content')
<div class="kt-container-fixed">
    <div class="flex flex-wrap items-center justify-between gap-5 pb-7.5 lg:items-end">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono">Account Codes</h1>
            <div class="flex items-center gap-2 text-sm font-normal text-secondary-foreground">
                Manage account codes and their department assignments
            </div>
        </div>
        @can(App\Enums\Tenant\PermissionKey::CreateAccountCode->value)
            <a class="kt-btn kt-btn-primary" href="{{ route('account-codes.create') }}">
                <i class="ki-filled ki-plus"></i>
                Add New Account Code
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
                <h3 class="kt-card-title">All Account Codes</h3>
                <div class="flex items-center gap-2">
                    <span class="kt-badge kt-badge-sm kt-badge-outline">
                        {{ $accountCodes->count() }} {{ Str::plural('Account Code', $accountCodes->count()) }}
                    </span>
                </div>
            </div>

            @if($accountCodes->isEmpty())
                <div class="kt-card-content flex flex-col gap-4 p-5 lg:p-7.5 lg:pt-4">
                    <div class="flex flex-col items-center justify-center py-12">
                        <i class="ki-filled ki-book text-6xl text-muted-foreground mb-4"></i>
                        <h3 class="text-lg font-medium text-foreground mb-2">No account codes found</h3>
                        <p class="text-sm text-secondary-foreground mb-4">Get started by creating your first account code</p>
                        @can(App\Enums\Tenant\PermissionKey::CreateAccountCode->value)
                            <a href="{{ route('account-codes.create') }}" class="kt-btn kt-btn-primary">
                                <i class="ki-filled ki-plus"></i>
                                Add Account Code
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
                                    <th class="min-w-[120px]">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">Code</span>
                                        </span>
                                    </th>
                                    <th class="min-w-[200px]">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">Name</span>
                                        </span>
                                    </th>
                                    <th class="min-w-[180px]">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">Department</span>
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
                                @foreach($accountCodes as $accountCode)
                                    <tr>
                                        <td>
                                            <span class="kt-badge kt-badge-sm kt-badge-primary">{{ $accountCode->id }}</span>
                                        </td>
                                        <td>
                                            <span class="kt-badge kt-badge-sm kt-badge-outline font-mono">{{ $accountCode->code }}</span>
                                        </td>
                                        <td>
                                            <span class="text-sm font-medium leading-none text-mono">{{ $accountCode->name }}</span>
                                        </td>
                                        <td>
                                            <span class="text-sm text-foreground">{{ $accountCode->department->name }}</span>
                                        </td>
                                        <td>
                                            <span class="text-sm text-foreground">{{ $accountCode->created_at->format('M d, Y') }}</span>
                                        </td>
                                        <td class="text-center">
                                            @can(App\Enums\Tenant\PermissionKey::AccessAccountCodes->value)
                                                <a href="{{ route('account-codes.edit', $accountCode) }}"
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
