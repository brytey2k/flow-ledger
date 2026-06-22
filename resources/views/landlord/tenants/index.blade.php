@extends('landlord.layouts.app')

@section('title', 'Tenants Management')

@section('content')
<!-- Container -->
<div class="kt-container-fixed">
    <div class="flex flex-wrap items-center justify-between gap-5 pb-7.5 lg:items-end">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono">
                Tenants Management
            </h1>
            <div class="flex items-center gap-2 text-sm font-normal text-secondary-foreground">
                Manage all tenants and their domains
            </div>
        </div>
        <div class="flex items-center gap-2.5">
            <a class="kt-btn kt-btn-primary" href="{{ route('landlord.tenants.create') }}">
                <i class="ki-filled ki-plus"></i>
                Add New Tenant
            </a>
        </div>
    </div>
</div>
<!-- End of Container -->

<!-- Container -->
<div class="kt-container-fixed">
    <div class="grid gap-5 lg:gap-7.5">
        <div class="kt-card kt-card-grid">
            <div class="kt-card-header">
                <h3 class="kt-card-title">
                    All Tenants
                </h3>
                <div class="flex items-center gap-2">
                    <span class="badge badge-sm badge-outline">
                        {{ $tenants->count() }} {{ Str::plural('Tenant', $tenants->count()) }}
                    </span>
                </div>
            </div>
            @if($tenants->isEmpty())
                <div class="kt-card-content flex flex-col gap-4 p-5 lg:p-7.5 lg:pt-4">
                    <div class="flex flex-col items-center justify-center py-12">
                        <i class="ki-filled ki-information-2 text-6xl text-muted-foreground mb-4"></i>
                        <h3 class="text-lg font-medium text-foreground mb-2">No tenants found</h3>
                        <p class="text-sm text-secondary-foreground mb-4">Get started by creating your first tenant</p>
                        <a href="{{ route('landlord.tenants.create') }}" class="kt-btn kt-btn-primary">
                            <i class="ki-filled ki-plus"></i>
                            Add Tenant
                        </a>
                    </div>
                </div>
            @else
                <div class="kt-card-table">
                    <div class="kt-scrollable-x-auto border-b border-border">
                        <table class="kt-table kt-table-border table-fixed">
                            <thead>
                                <tr>
                                    <th class="min-w-[150px]">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">Tenant</span>
                                            <span class="kt-table-col-sort"></span>
                                        </span>
                                    </th>
                                    <th class="min-w-[200px]">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">Domains</span>
                                            <span class="kt-table-col-sort"></span>
                                        </span>
                                    </th>
                                    <th class="min-w-[150px]">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">Created</span>
                                            <span class="kt-table-col-sort"></span>
                                        </span>
                                    </th>
                                    <th class="min-w-[120px]">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">Status</span>
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
                                @foreach($tenants as $tenant)
                                    @php
                                        $tenantName = trim((string) ($tenant->name ?? ''));
                                        if ($tenantName === '' && is_array($tenant->data ?? null)) {
                                            $tenantName = trim((string) ($tenant->data['name'] ?? ''));
                                        }
                                        $isSuspended = method_exists($tenant, 'isSuspended') ? $tenant->isSuspended() : false;
                                        $modalId = 'reset_tenant_modal_' . $tenant->id;
                                        $deleteModalId = 'delete_tenant_modal_' . $tenant->id;
                                    @endphp
                                    <tr>
                                        <td>
                                            <div class="flex flex-col gap-1">
                                                <span class="text-sm font-medium leading-none text-mono">{{ $tenant->id }}</span>
                                                @if($tenantName !== '')
                                                    <span class="text-2sm font-normal leading-3 text-secondary-foreground">{{ $tenantName }}</span>
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            <div class="flex flex-wrap items-center gap-1.5">
                                                @forelse($tenant->domains as $domain)
                                                    <span class="badge badge-sm badge-outline">{{ $domain->domain }}</span>
                                                @empty
                                                    <span class="text-2sm text-muted-foreground">No domains</span>
                                                @endforelse
                                            </div>
                                        </td>
                                        <td>
                                            <div class="flex flex-col gap-0.5">
                                                <span class="text-sm text-foreground">{{ $tenant->created_at->format('M d, Y') }}</span>
                                                <span class="text-2sm text-secondary-foreground">{{ $tenant->created_at->format('g:i A') }}</span>
                                            </div>
                                        </td>
                                        <td>
                                            @if($isSuspended)
                                                <span class="badge badge-sm badge-warning">Suspended</span>
                                            @else
                                                <span class="badge badge-sm badge-success">Active</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <div class="kt-menu" data-kt-menu="true">
                                                <div class="kt-menu-item" data-kt-menu-item-toggle="dropdown" data-kt-menu-item-trigger="click" data-kt-menu-item-placement="bottom-end">
                                                    <button class="kt-menu-toggle kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost">
                                                        <i class="ki-filled ki-dots-vertical text-lg"></i>
                                                    </button>
                                                    <div class="kt-menu-dropdown kt-menu-default w-full max-w-[200px]" data-kt-menu-dismiss="true">
                                                        <div class="kt-menu-item">
                                                            <a class="kt-menu-link" href="#">
                                                                <span class="kt-menu-icon">
                                                                    <i class="ki-filled ki-eye"></i>
                                                                </span>
                                                                <span class="kt-menu-title">View Details</span>
                                                            </a>
                                                        </div>
                                                        <div class="kt-menu-item">
                                                            <a class="kt-menu-link" href="{{ route('landlord.tenants.feature-flags.index', $tenant) }}">
                                                                <span class="kt-menu-icon">
                                                                    <i class="ki-filled ki-flag"></i>
                                                                </span>
                                                                <span class="kt-menu-title">Feature Flags</span>
                                                            </a>
                                                        </div>
                                                        <div class="kt-menu-item">
                                                            <a class="kt-menu-link" href="{{ route('landlord.tenants.select-user', $tenant) }}">
                                                                <span class="kt-menu-icon">
                                                                    <i class="ki-filled ki-user-tick"></i>
                                                                </span>
                                                                <span class="kt-menu-title">Impersonate User</span>
                                                            </a>
                                                        </div>
                                                        <div class="kt-menu-item">
                                                            <a class="kt-menu-link" href="#">
                                                                <span class="kt-menu-icon">
                                                                    <i class="ki-filled ki-setting-2"></i>
                                                                </span>
                                                                <span class="kt-menu-title">Manage</span>
                                                            </a>
                                                        </div>
                                                        <div class="kt-menu-item">
                                                            @if($isSuspended)
                                                                <form method="POST" action="{{ route('landlord.tenants.unsuspend', $tenant) }}" class="w-full">
                                                                    @csrf
                                                                    <button type="submit" class="kt-menu-link w-full text-start">
                                                                        <span class="kt-menu-icon">
                                                                            <i class="ki-filled ki-check-circle"></i>
                                                                        </span>
                                                                        <span class="kt-menu-title">Reactivate Tenant</span>
                                                                    </button>
                                                                </form>
                                                            @else
                                                                <form method="POST" action="{{ route('landlord.tenants.suspend', $tenant) }}" class="w-full">
                                                                    @csrf
                                                                    <button type="submit" class="kt-menu-link w-full text-start text-warning">
                                                                        <span class="kt-menu-icon">
                                                                            <i class="ki-filled ki-lock"></i>
                                                                        </span>
                                                                        <span class="kt-menu-title">Suspend Tenant</span>
                                                                    </button>
                                                                </form>
                                                            @endif
                                                        </div>
                                                        <div class="kt-menu-separator"></div>
                                                        @if($tenantName !== '')
                                                            <div class="kt-menu-item">
                                                                <a
                                                                    class="kt-menu-link text-warning"
                                                                    data-kt-modal-toggle="#{{ $modalId }}"
                                                                    href="#"
                                                                >
                                                                    <span class="kt-menu-icon">
                                                                        <i class="ki-filled ki-arrows-circle"></i>
                                                                    </span>
                                                                    <span class="kt-menu-title">Reset Tenant</span>
                                                                </a>
                                                            </div>
                                                        @endif
                                                        <div class="kt-menu-separator"></div>
                                                        <div class="kt-menu-item">
                                                            <a
                                                                class="kt-menu-link text-danger"
                                                                data-kt-modal-toggle="#{{ $deleteModalId }}"
                                                                href="#"
                                                            >
                                                                <span class="kt-menu-icon">
                                                                    <i class="ki-filled ki-trash"></i>
                                                                </span>
                                                                <span class="kt-menu-title">Delete</span>
                                                            </a>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @foreach($tenants as $tenant)
                        @php
                            $tenantName = trim((string) ($tenant->name ?? ''));
                            if ($tenantName === '' && is_array($tenant->data ?? null)) {
                                $tenantName = trim((string) ($tenant->data['name'] ?? ''));
                            }
                            $modalId = 'reset_tenant_modal_' . $tenant->id;
                            $deleteModalId = 'delete_tenant_modal_' . $tenant->id;
                            $confirmName = $tenantName !== '' ? $tenantName : $tenant->id;
                        @endphp

                        {{-- Reset Modal --}}
                        @if($tenantName !== '')
                            <div class="kt-modal" data-kt-modal="true" data-kt-modal-backdrop-static="true" id="{{ $modalId }}">
                                <div class="kt-modal-content w-full top-[10%]" style="max-width: 400px;">
                                    <div class="kt-modal-header">
                                        <h3 class="kt-modal-title text-warning">Reset Tenant: {{ $tenant->id }}</h3>
                                        <button
                                            type="button"
                                            class="kt-modal-close"
                                            aria-label="Close modal"
                                            data-kt-modal-dismiss="#{{ $modalId }}"
                                        >
                                            <i class="ki-filled ki-cross"></i>
                                        </button>
                                    </div>
                                    <form method="POST" action="{{ route('landlord.tenants.reset', $tenant) }}" class="kt-modal-body grid gap-4">
                                        @csrf

                                        <div class="rounded-lg border border-warning/30 bg-warning/10 p-4 text-sm text-warning">
                                            This will erase all current tenant data and recreate the database to a fresh bootstrap state.
                                        </div>

                                        <div class="text-sm text-secondary-foreground">
                                            Type tenant name <span class="font-semibold text-foreground">{{ $tenantName }}</span> to confirm.
                                        </div>

                                        <div class="grid gap-2">
                                            <label class="kt-form-label" for="confirm_tenant_name_{{ $tenant->id }}">
                                                Confirm Tenant Name
                                            </label>
                                            <input
                                                id="confirm_tenant_name_{{ $tenant->id }}"
                                                name="confirm_tenant_name"
                                                type="text"
                                                class="kt-input"
                                                placeholder="Type exact tenant name"
                                                required
                                            >
                                        </div>

                                        <div class="kt-modal-footer gap-2">
                                            <button class="kt-btn kt-btn-light" data-kt-modal-dismiss="#{{ $modalId }}" type="button">Cancel</button>
                                            <button class="kt-btn kt-btn-warning" type="submit">Confirm Reset</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        @endif

                        {{-- Delete Modal --}}
                        <div class="kt-modal" data-kt-modal="true" data-kt-modal-backdrop-static="true" id="{{ $deleteModalId }}">
                            <div class="kt-modal-content w-full top-[10%]" style="max-width: 420px;">
                                <div class="kt-modal-header">
                                    <h3 class="kt-modal-title text-danger">Delete Tenant: {{ $tenant->id }}</h3>
                                    <button
                                        type="button"
                                        class="kt-modal-close"
                                        aria-label="Close modal"
                                        data-kt-modal-dismiss="#{{ $deleteModalId }}"
                                    >
                                        <i class="ki-filled ki-cross"></i>
                                    </button>
                                </div>
                                <form
                                    method="POST"
                                    action="{{ route('landlord.tenants.destroy', $tenant) }}"
                                    class="kt-modal-body grid gap-4"
                                >
                                    @csrf
                                    @method('DELETE')

                                    <div class="rounded-lg border border-danger/30 bg-danger/10 p-4 text-sm text-danger">
                                        This action is <span class="font-semibold">permanent and irreversible</span>. The tenant record and all associated domains will be removed.
                                    </div>

                                    <div class="text-sm text-secondary-foreground">
                                        Type <span class="font-semibold text-foreground">{{ $confirmName }}</span> to confirm.
                                    </div>

                                    <div class="grid gap-2">
                                        <label class="kt-form-label" for="del_confirm_tenant_name_{{ $tenant->id }}">
                                            Confirm Tenant Name
                                        </label>
                                        <input
                                            id="del_confirm_tenant_name_{{ $tenant->id }}"
                                            name="confirm_tenant_name"
                                            type="text"
                                            class="kt-input"
                                            placeholder="Type exact tenant name"
                                            required
                                        >
                                    </div>

                                    <div class="flex items-start gap-3 rounded-lg border border-border p-3">
                                        <input
                                            id="delete_database_{{ $tenant->id }}"
                                            name="delete_database"
                                            type="checkbox"
                                            value="1"
                                            class="kt-checkbox mt-0.5"
                                        >
                                        <div class="grid gap-1">
                                            <label class="text-sm font-medium text-foreground cursor-pointer" for="delete_database_{{ $tenant->id }}">
                                                Also delete the tenant database
                                            </label>
                                            <span class="text-2sm text-secondary-foreground">
                                                Leave unchecked to keep the database for backup purposes.
                                            </span>
                                        </div>
                                    </div>

                                    <div class="kt-modal-footer gap-2">
                                        <button class="kt-btn kt-btn-light" data-kt-modal-dismiss="#{{ $deleteModalId }}" type="button">Cancel</button>
                                        <button class="kt-btn kt-btn-danger" type="submit">Delete Tenant</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
<!-- End of Container -->
@endsection
