@extends('tenant.layouts.base')

@section('content')
<div class="kt-container-fixed">
    <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono">Edit Position</h1>
            <div class="flex items-center gap-2 text-sm font-normal text-secondary-foreground">
                Update position details
            </div>
        </div>
        <div class="flex items-center gap-2.5">
            <a class="kt-btn kt-btn-outline" href="{{ route('positions.index') }}">
                <i class="ki-filled ki-arrow-left"></i>
                Back to Positions
            </a>
        </div>
    </div>
</div>

<div class="kt-container-fixed">
    <div class="grid gap-5 lg:gap-7.5">
        <div class="kt-card">
            <div class="kt-card-header">
                <h3 class="kt-card-title">Position Details</h3>
            </div>
            <div class="kt-card-content">
                <form method="POST" action="{{ route('positions.update', $position) }}" class="grid gap-7">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                        <div class="col-span-1">
                            <label class="kt-form-label block mb-2" for="name">
                                Position Name <span class="text-destructive">*</span>
                            </label>
                            <input id="name" name="name" type="text" value="{{ old('name', $position->name) }}"
                                   class="kt-input w-full" placeholder="e.g. Senior Accountant" required
                                   aria-invalid="@error('name') true @else false @enderror" />
                            <div class="mt-1 text-xs text-muted-foreground">
                                Enter a descriptive name for this position.
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
                                Update Position
                            </button>
                            <a class="kt-btn kt-btn-light" href="{{ route('positions.index') }}">Cancel</a>
                        </div>
                        @can(App\Enums\Tenant\PermissionKey::DeletePosition->value)
                            <button type="button" class="kt-btn kt-btn-danger"
                                    onclick="if(confirm('Are you sure you want to delete this position? This action cannot be undone.')) { document.getElementById('delete-position-form').submit(); }">
                                <i class="ki-filled ki-trash"></i>
                                Delete Position
                            </button>
                        @endcan
                    </div>
                </form>

                @can(App\Enums\Tenant\PermissionKey::DeletePosition->value)
                    <form id="delete-position-form" action="{{ route('positions.destroy', $position) }}" method="POST" class="hidden">
                        @csrf @method('DELETE')
                    </form>
                @endcan
            </div>
        </div>
    </div>
</div>
@endsection
