@extends('tenant.layouts.base')

@section('content')
<!-- Container -->
<div class="kt-container-fixed">
    <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono">Manage Role Permissions</h1>
            <div class="flex items-center gap-2 text-sm font-normal text-secondary-foreground">
                Assign permissions to {{ $role->name }} role
            </div>
        </div>
        <div class="flex items-center gap-2.5">
            <a class="kt-btn kt-btn-outline" href="{{ route('roles.edit', $role) }}">
                <i class="ki-filled ki-arrow-left"></i>
                Back to Edit Role
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
                <h3 class="kt-card-title">Role Permissions</h3>
            </div>
            <div class="kt-card-content">
                <form method="POST" action="{{ route('roles.permissions.update', $role) }}" class="grid gap-7">
                    @csrf
                    @method('PUT')

                    <div class="p-4 rounded-lg bg-muted/50">
                        <p class="text-sm text-secondary-foreground">
                            <i class="ki-filled ki-information-2"></i>
                            Select the permissions that should be assigned to this role.
                            All users with this role will inherit these permissions.
                        </p>
                    </div>

                    @if(!$permissions->isEmpty())
                        <div class="flex items-center gap-2 pb-4 border-b">
                            <input type="checkbox" id="select-all-permissions" class="cursor-pointer" />
                            <label for="select-all-permissions" class="text-sm font-medium cursor-pointer">
                                Select All Permissions
                            </label>
                        </div>
                    @endif

                    <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-4">
                        @if($permissions->isEmpty())
                            <div class="col-span-full">
                                <p class="text-sm text-muted-foreground">No permissions available in the system.</p>
                            </div>
                        @else
                            @foreach($permissions as $permission)
                                <div class="flex items-start gap-2">
                                    <input
                                        type="checkbox"
                                        id="permission-{{ $permission->id }}"
                                        name="permissions[]"
                                        value="{{ $permission->id }}"
                                        {{ in_array($permission->id, old('permissions', $role->permissions->pluck('id')->toArray())) ? 'checked' : '' }}
                                        class="mt-1 permission-checkbox"
                                    />
                                    <label for="permission-{{ $permission->id }}" class="text-sm cursor-pointer">
                                        {{ $permission->name }}
                                    </label>
                                </div>
                            @endforeach
                        @endif
                    </div>

                    <div class="pt-5 mt-2 flex justify-start items-center gap-2.5 border-t">
                        <button type="submit" class="kt-btn kt-btn-primary">
                            <i class="ki-filled ki-check"></i>
                            Update Permissions
                        </button>
                        <a class="kt-btn kt-btn-light" href="{{ route('roles.edit', $role) }}">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<!-- End of Container -->
@endsection

@push('page_js')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const selectAllCheckbox = document.getElementById('select-all-permissions');
        const permissionCheckboxes = document.querySelectorAll('.permission-checkbox');

        if (selectAllCheckbox && permissionCheckboxes.length > 0) {
            function updateSelectAllState() {
                const allChecked = Array.from(permissionCheckboxes).every(cb => cb.checked);
                const someChecked = Array.from(permissionCheckboxes).some(cb => cb.checked);
                selectAllCheckbox.checked = allChecked;
                selectAllCheckbox.indeterminate = someChecked && !allChecked;
            }

            selectAllCheckbox.addEventListener('change', function() {
                permissionCheckboxes.forEach(cb => { cb.checked = this.checked; });
            });

            permissionCheckboxes.forEach(cb => {
                cb.addEventListener('change', updateSelectAllState);
            });

            updateSelectAllState();
        }
    });
</script>
@endpush
