@extends('landlord.layouts.app')

@section('title', 'Edit Tenant')

@section('content')
<div class="sgh-container-fixed">
    <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono">Edit Tenant</h1>
            <div class="flex items-center gap-2 text-sm font-normal text-secondary-foreground">
                Update tenant details, including its linked IDP tenant
            </div>
        </div>
        <div class="flex items-center gap-2.5">
            <a class="sgh-btn sgh-btn-outline" href="{{ route('landlord.tenants.index') }}">
                <x-tabler-arrow-left />
                Back to Tenants
            </a>
        </div>
    </div>
</div>

<div class="sgh-container-fixed">
    @if ($errors->any())
        <div class="mb-5 rounded-lg border border-destructive/30 bg-destructive/10 p-4 text-destructive">
            <div class="font-medium mb-2">Please fix the following errors:</div>
            <ul class="list-disc ps-5 text-sm">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid gap-5 lg:gap-7.5">
        <div class="sgh-card">
            <div class="sgh-card-header">
                <h3 class="sgh-card-title">Tenant Details</h3>
            </div>
            <div class="sgh-card-content">
                <form method="POST" action="{{ route('landlord.tenants.update', $tenant) }}" class="grid gap-7">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                        {{-- Tenant ID (read-only) --}}
                        <div>
                            <label class="sgh-form-label block mb-2" for="id">Subdomain</label>
                            <input id="id" type="text" value="{{ $tenant->id }}" class="sgh-input w-full" disabled />
                            <div class="mt-1 text-xs text-muted-foreground">
                                The subdomain cannot be changed after creation.
                            </div>
                        </div>

                        {{-- Tenant Name --}}
                        <div>
                            <label class="sgh-form-label block mb-2" for="name">
                                Organisation Name <span class="text-destructive">*</span>
                            </label>
                            <input id="name" name="name" type="text" value="{{ old('name', $tenant->name) }}"
                                   class="sgh-input w-full" placeholder="e.g. Acme Corp"
                                   required aria-invalid="@error('name') true @else false @enderror" />
                            @error('name')
                                <p class="mt-1 text-sm text-destructive">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- IDP Tenant --}}
                        <div class="lg:col-span-2">
                            <label class="sgh-form-label block mb-2" for="idp_tenant_id">IDP Tenant</label>
                            @if(count($idpTenants) > 0)
                                <select id="idp_tenant_id" name="idp_tenant_id" class="sgh-select w-full">
                                    <option value="">— None / not linked —</option>
                                    @foreach($idpTenants as $idpTenant)
                                        <option value="{{ $idpTenant['id'] }}" @selected(old('idp_tenant_id', (string) $tenant->idp_tenant_id) == $idpTenant['id'])>
                                            {{ $idpTenant['name'] }} ({{ $idpTenant['slug'] }})
                                        </option>
                                    @endforeach
                                </select>
                            @else
                                <input id="idp_tenant_id" name="idp_tenant_id" type="text" value="{{ old('idp_tenant_id', $tenant->idp_tenant_id) }}"
                                       class="sgh-input w-full" placeholder="IDP tenant ID (optional)" />
                                <p class="mt-1 text-sm text-secondary-foreground">IDP tenant list unavailable — enter the ID manually or leave blank.</p>
                            @endif
                            @error('idp_tenant_id')
                                <p class="mt-1 text-sm text-destructive">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="pt-5 mt-2 flex justify-start items-center gap-2.5">
                        <button type="submit" class="sgh-btn sgh-btn-primary">
                            <x-tabler-check-filled />
                            Save Changes
                        </button>
                        <a class="sgh-btn sgh-btn-light" href="{{ route('landlord.tenants.index') }}">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
