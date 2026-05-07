@extends('landlord.layouts.app')

@section('title', 'Feature Flags - ' . ($tenant->data['name'] ?? $tenant->id))

@section('content')
<!-- Container -->
<div class="kt-container-fixed">
    <div class="flex flex-wrap items-center justify-between gap-5 pb-7.5 lg:items-end">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono">
                Feature Flags
            </h1>
            <div class="flex items-center gap-2 text-sm font-normal text-secondary-foreground">
                <a href="{{ route('landlord.tenants.index') }}" class="hover:text-primary">Tenants</a>
                <span>/</span>
                <span>{{ $tenant->data['name'] ?? $tenant->id }}</span>
                <span>/</span>
                <span>Feature Flags</span>
            </div>
        </div>
        <div class="flex items-center gap-2.5">
            <a class="kt-btn kt-btn-outline" href="{{ route('landlord.tenants.index') }}">
                <i class="ki-filled ki-arrow-left"></i>
                Back to Tenants
            </a>
        </div>
    </div>
</div>
<!-- End of Container -->

<!-- Container -->
<div class="kt-container-fixed">
    <div class="grid gap-5 lg:gap-7.5">
        <!-- Tenant Feature Flags Card -->
        <div class="kt-card">
            <div class="kt-card-header">
                <h3 class="kt-card-title">
                    Manage Feature Flags for {{ $tenant->data['name'] ?? $tenant->id }}
                </h3>
                <div class="flex items-center gap-2">
                    @php
                        $enabledCount = count(array_filter($flags));
                        $totalCount = count($flags);
                    @endphp
                    <span class="badge badge-sm badge-outline">
                        {{ $enabledCount }}/{{ $totalCount }} enabled
                    </span>
                </div>
            </div>
            <div class="kt-card-content">
                <form action="{{ route('landlord.tenants.feature-flags.update', $tenant) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="overflow-x-auto">
                        <table class="kt-table kt-table-border">
                            <thead>
                                <tr>
                                    <th class="w-12">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">Enabled</span>
                                        </span>
                                    </th>
                                    <th class="min-w-[200px]">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">Feature</span>
                                        </span>
                                    </th>
                                    <th class="min-w-[150px]">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">Status</span>
                                        </span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($flagDefinitions as $featureFlag)
                                    <tr>
                                        <td class="text-center">
                                            <input
                                                type="checkbox"
                                                name="flags[]"
                                                value="{{ $featureFlag->value }}"
                                                class="kt-checkbox"
                                                id="flag_{{ $featureFlag->value }}"
                                                {{ ($flags[$featureFlag->value] ?? false) ? 'checked' : '' }}
                                            />
                                        </td>
                                        <td>
                                            <label for="flag_{{ $featureFlag->value }}" class="cursor-pointer">
                                                <span class="text-sm font-medium text-foreground">{{ $featureFlag->label() }}</span>
                                                <span class="block text-2sm text-secondary-foreground">{{ $featureFlag->value }}</span>
                                            </label>
                                        </td>
                                        <td>
                                            @if($flags[$featureFlag->value] ?? false)
                                                <span class="badge badge-sm badge-success badge-outline">Enabled</span>
                                            @else
                                                <span class="badge badge-sm badge-secondary badge-outline">Disabled</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @error('flags')
                        <div class="text-sm text-destructive mt-2">{{ $message }}</div>
                    @enderror
                    @error('flags.*')
                        <div class="text-sm text-destructive mt-2">{{ $message }}</div>
                    @enderror

                    <div class="flex justify-end mt-6">
                        <button type="submit" class="kt-btn kt-btn-primary">
                            <i class="ki-filled ki-check"></i>
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Bulk Operations Card -->
        <div class="kt-card">
            <div class="kt-card-header">
                <h3 class="kt-card-title">
                    Bulk Operations
                </h3>
                <div class="flex items-center gap-2">
                    <span class="badge badge-sm badge-outline badge-warning">
                        Affects all tenants
                    </span>
                </div>
            </div>
            <div class="kt-card-content">
                <p class="text-sm text-secondary-foreground mb-4">
                    Use bulk operations to enable or disable a feature for all tenants at once.
                    This will override individual tenant settings.
                </p>

                <form action="{{ route('landlord.feature-flags.bulk-update') }}" method="POST" class="flex flex-wrap items-end gap-4">
                    @csrf

                    <div class="flex-1 min-w-[200px]">
                        <label class="kt-form-label mb-2 block">
                            Select Feature
                        </label>
                        <select name="flag" class="kt-input w-full" required>
                            <option value="">Choose a feature...</option>
                            @foreach($flagDefinitions as $featureFlag)
                                <option value="{{ $featureFlag->value }}">{{ $featureFlag->label() }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex gap-2">
                        <button type="submit" name="action" value="enable" class="kt-btn kt-btn-success">
                            <i class="ki-filled ki-check-circle"></i>
                            Enable for All
                        </button>
                        <button type="submit" name="action" value="disable" class="kt-btn kt-btn-danger">
                            <i class="ki-filled ki-cross-circle"></i>
                            Disable for All
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<!-- End of Container -->
@endsection
