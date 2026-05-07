@extends('tenant.layouts.base')

@section('content')
<!-- Container -->
<div class="kt-container-fixed">
    <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono">Edit Department</h1>
            <div class="flex items-center gap-2 text-sm font-normal text-secondary-foreground">
                Update department details
            </div>
        </div>
        <div class="flex items-center gap-2.5">
            <a class="kt-btn kt-btn-outline" href="{{ route('departments.index') }}">
                <i class="ki-filled ki-arrow-left"></i>
                Back to Departments
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
                <h3 class="kt-card-title">Department Details</h3>
            </div>
            <div class="kt-card-content">
                <form method="POST" action="{{ route('departments.update', $department) }}" class="grid gap-7">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                        <div class="col-span-1 lg:col-span-1">
                            <label class="kt-form-label block mb-2" for="name">
                                Department Name <span class="text-destructive">*</span>
                            </label>
                            <input id="name" name="name" type="text" value="{{ old('name', $department->name) }}"
                                   class="kt-input w-full" placeholder="e.g. Finance" required
                                   aria-invalid="@error('name') true @else false @enderror" />
                            <div class="mt-1 text-xs text-muted-foreground">
                                Enter a descriptive name for this department.
                            </div>
                            @error('name')
                                <p class="mt-1 text-sm text-destructive">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="pt-5 mt-2 flex justify-between items-center">
                        <div class="flex items-center gap-2.5">
                            <button type="submit" class="kt-btn kt-btn-primary">
                                <i class="ki-filled ki-check"></i>
                                Update Department
                            </button>
                            <a class="kt-btn kt-btn-light" href="{{ route('departments.index') }}">Cancel</a>
                        </div>
                        @can(App\Enums\Tenant\PermissionKey::DeleteDepartment->value)
                            <button type="button" class="kt-btn kt-btn-danger"
                                    onclick="if(confirm('Are you sure you want to delete this department? This action cannot be undone.')) { document.getElementById('delete-department-form').submit(); }">
                                <i class="ki-filled ki-trash"></i>
                                Delete Department
                            </button>
                        @endcan
                    </div>
                </form>

                @can(App\Enums\Tenant\PermissionKey::DeleteDepartment->value)
                    <form id="delete-department-form" action="{{ route('departments.destroy', $department) }}" method="POST" class="hidden">
                        @csrf @method('DELETE')
                    </form>
                @endcan
            </div>
        </div>
    </div>
</div>
<!-- End of Container -->
@endsection
