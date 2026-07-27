@php
    /**
     * @var \Illuminate\Support\Collection<int, \App\Models\Permission> $permissions
     * @var array<int, int> $selectedIds
     */
    $groupedPermissions = $permissions
        ->groupBy(fn($permission) => (\App\Enums\Tenant\PermissionKey::tryFrom($permission->name)?->category() ?? \App\Enums\Tenant\PermissionCategory::Other)->value);
@endphp

@if($permissions->isEmpty())
    <div class="col-span-full">
        <p class="text-sm text-muted-foreground">{{ __('roles.permissions.none') }}</p>
    </div>
@else
    <div class="flex items-center gap-2 pb-4 border-b">
        <input
            type="checkbox"
            id="select-all-permissions"
            class="cursor-pointer"
        />
        <label for="select-all-permissions" class="text-sm font-medium cursor-pointer">
            {{ __('roles.permissions.select_all') }}
        </label>
    </div>

    <div class="grid gap-6">
        @foreach(\App\Enums\Tenant\PermissionCategory::cases() as $category)
            @continue(!isset($groupedPermissions[$category->value]))
            <div>
                <h4 class="text-sm font-semibold text-secondary-foreground mb-3">
                    {{ $category->label() }}
                </h4>
                <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-4">
                    @foreach($groupedPermissions[$category->value] as $permission)
                        <div class="flex items-start gap-2">
                            <input
                                type="checkbox"
                                id="permission-{{ $permission->id }}"
                                name="permissions[]"
                                value="{{ $permission->id }}"
                                {{ in_array($permission->id, $selectedIds) ? 'checked' : '' }}
                                class="mt-1 permission-checkbox"
                            />
                            <label for="permission-{{ $permission->id }}" class="text-sm cursor-pointer">
                                {{ ucwords($permission->name) }}
                            </label>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>

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
                    permissionCheckboxes.forEach(cb => {
                        cb.checked = this.checked;
                    });
                });

                permissionCheckboxes.forEach(cb => {
                    cb.addEventListener('change', updateSelectAllState);
                });

                updateSelectAllState();
            }
        });
    </script>
@endif
