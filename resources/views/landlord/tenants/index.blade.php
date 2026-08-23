@extends('landlord.layouts.app')

@section('title', 'Tenants Management')

@section('content')
<!-- Container -->
<div class="sgh-container-fixed">
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
            <a class="sgh-btn sgh-btn-primary" href="{{ route('landlord.tenants.create') }}">
                <x-tabler-plus-filled />
                Add New Tenant
            </a>
        </div>
    </div>
</div>
<!-- End of Container -->

<!-- Container -->
<div class="sgh-container-fixed">
    <div class="grid gap-5 lg:gap-7.5">
        <div class="sgh-card sgh-card-grid">
            <div class="sgh-card-header">
                <h3 class="sgh-card-title">
                    All Tenants
                </h3>
                <div class="flex items-center gap-2">
                    <span class="badge badge-sm badge-outline">
                        {{ $tenants->count() }} {{ Str::plural('Tenant', $tenants->count()) }}
                    </span>
                </div>
            </div>
            @if($tenants->isEmpty())
                <div class="sgh-card-content flex flex-col gap-4 p-5 lg:p-7.5 lg:pt-4">
                    <div class="flex flex-col items-center justify-center py-12">
                        <x-tabler-info-square-filled class="text-6xl text-muted-foreground mb-4" />
                        <h3 class="text-lg font-medium text-foreground mb-2">No tenants found</h3>
                        <p class="text-sm text-secondary-foreground mb-4">Get started by creating your first tenant</p>
                        <a href="{{ route('landlord.tenants.create') }}" class="sgh-btn sgh-btn-primary">
                            <x-tabler-plus-filled />
                            Add Tenant
                        </a>
                    </div>
                </div>
            @else
                <div class="sgh-card-table">
                    <div class="sgh-scrollable-x-auto border-b border-border">
                        <table class="sgh-table sgh-table-border table-fixed">
                            <thead>
                                <tr>
                                    <th class="min-w-[150px]">
                                        <span class="sgh-table-col">
                                            <span class="sgh-table-col-label">Tenant</span>
                                            <span class="sgh-table-col-sort"></span>
                                        </span>
                                    </th>
                                    <th class="min-w-[200px]">
                                        <span class="sgh-table-col">
                                            <span class="sgh-table-col-label">Domains</span>
                                            <span class="sgh-table-col-sort"></span>
                                        </span>
                                    </th>
                                    <th class="min-w-[150px]">
                                        <span class="sgh-table-col">
                                            <span class="sgh-table-col-label">Created</span>
                                            <span class="sgh-table-col-sort"></span>
                                        </span>
                                    </th>
                                    <th class="min-w-[120px]">
                                        <span class="sgh-table-col">
                                            <span class="sgh-table-col-label">Status</span>
                                        </span>
                                    </th>
                                    <th class="min-w-[110px]">
                                        <span class="sgh-table-col">
                                            <span class="sgh-table-col-label">IDP Linked</span>
                                        </span>
                                    </th>
                                    <th class="min-w-[100px] text-center">
                                        <span class="sgh-table-col">
                                            <span class="sgh-table-col-label">Actions</span>
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
                                        <td>
                                            @if(filled($tenant->idp_tenant_id))
                                                <span class="badge badge-sm badge-success">Yes</span>
                                            @else
                                                <span class="badge badge-sm badge-outline">No</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <div class="relative inline-block" x-data="tableDropdown">
                                                <button type="button" class="sgh-btn sgh-btn-sm sgh-btn-icon sgh-btn-ghost" @click="toggle($event)" :aria-expanded="open">
                                                    <x-tabler-dots-vertical-filled class="text-lg" />
                                                </button>
                                                <template x-teleport="body">
                                                <div x-show="open" x-cloak @click.outside="close()" @click="close()" class="sgh-dropdown-panel w-full max-w-[200px]" :style="`position:fixed;top:${y}px;left:${x - 200}px`">
                                                    <a class="sgh-dropdown-item" href="#">
                                                        <x-tabler-eye-filled />
                                                        <span>View Details</span>
                                                    </a>
                                                    <a class="sgh-dropdown-item" href="{{ route('landlord.tenants.feature-flags.index', $tenant) }}">
                                                        <x-tabler-flag-filled />
                                                        <span>Feature Flags</span>
                                                    </a>
                                                    <a class="sgh-dropdown-item" href="{{ route('landlord.tenants.roles.index', $tenant) }}">
                                                        <x-tabler-shield-check-filled />
                                                        <span>Roles & Permissions</span>
                                                    </a>
                                                    <a class="sgh-dropdown-item" href="{{ route('landlord.tenants.users-permissions.index', $tenant) }}">
                                                        <x-tabler-users-group />
                                                        <span>Users</span>
                                                    </a>
                                                    <a class="sgh-dropdown-item" href="{{ route('landlord.tenants.select-user', $tenant) }}">
                                                        <x-tabler-user-check />
                                                        <span>Impersonate User</span>
                                                    </a>
                                                    <a class="sgh-dropdown-item" href="{{ route('landlord.tenants.edit', $tenant) }}">
                                                        <x-tabler-settings-filled />
                                                        <span>Edit Tenant</span>
                                                    </a>
                                                    @if($isSuspended)
                                                        <form method="POST" action="{{ route('landlord.tenants.unsuspend', $tenant) }}">
                                                            @csrf
                                                            <button type="submit" class="sgh-dropdown-item">
                                                                <x-tabler-circle-check-filled />
                                                                <span>Reactivate Tenant</span>
                                                            </button>
                                                        </form>
                                                    @else
                                                        <form method="POST" action="{{ route('landlord.tenants.suspend', $tenant) }}">
                                                            @csrf
                                                            <button type="submit" class="sgh-dropdown-item text-warning">
                                                                <x-tabler-lock-filled />
                                                                <span>Suspend Tenant</span>
                                                            </button>
                                                        </form>
                                                    @endif
                                                    @if($tenantName !== '')
                                                        <div class="sgh-dropdown-separator"></div>
                                                        <button type="button" class="sgh-dropdown-item text-warning" @click="$store.modal.show('{{ $modalId }}')">
                                                            <x-tabler-arrows-diagonal />
                                                            <span>Reset Tenant</span>
                                                        </button>
                                                    @endif
                                                    <div class="sgh-dropdown-separator"></div>
                                                    <button type="button" class="sgh-dropdown-item text-danger" @click="$store.modal.show('{{ $deleteModalId }}')">
                                                        <x-tabler-trash-filled />
                                                        <span>Delete</span>
                                                    </button>
                                                </div>
                                                </template>
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
                            <div x-show="$store.modal.openId === '{{ $modalId }}'" x-cloak class="fixed inset-0 z-40 flex items-start justify-center overflow-y-auto p-4 pt-[10%]">
                                <div class="sgh-modal-backdrop" @click="$store.modal.hide()"></div>
                                <div class="sgh-modal-content" style="max-width: 400px;">
                                    <div class="sgh-modal-header">
                                        <h3 class="sgh-modal-title text-warning">Reset Tenant: {{ $tenant->id }}</h3>
                                        <button
                                            type="button"
                                            class="sgh-modal-close"
                                            aria-label="Close modal"
                                            @click="$store.modal.hide()"
                                        >
                                            <x-tabler-x-filled />
                                        </button>
                                    </div>
                                    <form method="POST" action="{{ route('landlord.tenants.reset', $tenant) }}" class="sgh-modal-body grid gap-4">
                                        @csrf

                                        <div class="rounded-lg border border-warning/30 bg-warning/10 p-4 text-sm text-warning">
                                            This will erase all current tenant data and recreate the database to a fresh bootstrap state.
                                        </div>

                                        <div class="text-sm text-secondary-foreground">
                                            Type tenant name <span class="font-semibold text-foreground">{{ $tenantName }}</span> to confirm.
                                        </div>

                                        <div class="grid gap-2">
                                            <label class="sgh-form-label" for="confirm_tenant_name_{{ $tenant->id }}">
                                                Confirm Tenant Name
                                            </label>
                                            <input
                                                id="confirm_tenant_name_{{ $tenant->id }}"
                                                name="confirm_tenant_name"
                                                type="text"
                                                class="sgh-input"
                                                placeholder="Type exact tenant name"
                                                required
                                            >
                                        </div>

                                        <div class="sgh-modal-footer gap-2">
                                            <button class="sgh-btn sgh-btn-light" @click="$store.modal.hide()" type="button">Cancel</button>
                                            <button class="sgh-btn sgh-btn-warning" type="submit">Confirm Reset</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        @endif

                        {{-- Delete Modal --}}
                        <div x-show="$store.modal.openId === '{{ $deleteModalId }}'" x-cloak class="fixed inset-0 z-40 flex items-start justify-center overflow-y-auto p-4 pt-[10%]">
                            <div class="sgh-modal-backdrop" @click="$store.modal.hide()"></div>
                            <div class="sgh-modal-content" style="max-width: 420px;">
                                <div class="sgh-modal-header">
                                    <h3 class="sgh-modal-title text-danger">Delete Tenant: {{ $tenant->id }}</h3>
                                    <button
                                        type="button"
                                        class="sgh-modal-close"
                                        aria-label="Close modal"
                                        @click="$store.modal.hide()"
                                    >
                                        <x-tabler-x-filled />
                                    </button>
                                </div>
                                <form
                                    method="POST"
                                    action="{{ route('landlord.tenants.destroy', $tenant) }}"
                                    class="sgh-modal-body grid gap-4"
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
                                        <label class="sgh-form-label" for="del_confirm_tenant_name_{{ $tenant->id }}">
                                            Confirm Tenant Name
                                        </label>
                                        <input
                                            id="del_confirm_tenant_name_{{ $tenant->id }}"
                                            name="confirm_tenant_name"
                                            type="text"
                                            class="sgh-input"
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
                                            class="sgh-checkbox mt-0.5"
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

                                    <div class="sgh-modal-footer gap-2">
                                        <button class="sgh-btn sgh-btn-light" @click="$store.modal.hide()" type="button">Cancel</button>
                                        <button class="sgh-btn sgh-btn-danger" type="submit">Delete Tenant</button>
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
