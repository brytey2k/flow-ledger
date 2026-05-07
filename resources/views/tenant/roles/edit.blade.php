@extends('tenant.layouts.base')

@section('content')
<!-- Container -->
<div class="kt-container-fixed">
    <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono">Edit Role</h1>
            <div class="flex items-center gap-2 text-sm font-normal text-secondary-foreground">
                Update role information
            </div>
        </div>
        <div class="flex items-center gap-2.5">
            <a class="kt-btn kt-btn-outline" href="{{ route('roles.index') }}">
                <i class="ki-filled ki-arrow-left"></i>
                Back to Roles
            </a>
        </div>
    </div>
</div>
<!-- End of Container -->

<!-- Container -->
<div class="kt-container-fixed">
    <div class="grid gap-5 lg:gap-7.5">
        <div class="kt-card">
            <div class="kt-card-header">
                <h3 class="kt-card-title">Role Details</h3>
            </div>
            <div class="kt-card-content">
                <form method="POST" action="{{ route('roles.update', $role) }}" class="grid gap-7">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 gap-5">
                        <div>
                            <label class="kt-form-label block mb-2" for="name">
                                Role Name <span class="text-destructive">*</span>
                            </label>
                            <input id="name" name="name" type="text" value="{{ old('name', $role->name) }}" class="kt-input w-full" placeholder="e.g. Administrator" required aria-invalid="@error('name') true @else false @enderror" />
                            <p class="mt-1 text-xs text-muted-foreground">Enter a descriptive name for this role.</p>
                            @error('name')
                                <p class="mt-1 text-sm text-destructive">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="pt-5 mt-2 flex justify-between items-center gap-2.5">
                        <div class="flex items-center gap-2.5">
                            <button type="submit" class="kt-btn kt-btn-primary">
                                <i class="ki-filled ki-check"></i>
                                Update Role
                            </button>
                            <a class="kt-btn kt-btn-outline" href="{{ route('roles.permissions.edit', $role) }}">
                                <i class="ki-filled ki-security-user"></i>
                                Manage Permissions
                            </a>
                            <a class="kt-btn kt-btn-light" href="{{ route('roles.index') }}">Cancel</a>
                        </div>
                        @can(\App\Enums\Tenant\PermissionKey::DeleteRole->value)
                            <form action="{{ route('roles.destroy', $role) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this role?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="kt-btn kt-btn-danger">
                                    <i class="ki-filled ki-trash"></i>
                                    Delete
                                </button>
                            </form>
                        @endcan
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<!-- End of Container -->
@endsection
