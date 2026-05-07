@extends('tenant.layouts.base')

@section('content')
<!-- Container -->
<div class="kt-container-fixed">
    <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono">Edit Level</h1>
            <div class="flex items-center gap-2 text-sm font-normal text-secondary-foreground">
                Update level details and hierarchy position
            </div>
        </div>
        <div class="flex items-center gap-2.5">
            <a class="kt-btn kt-btn-outline" href="{{ route('levels.index') }}">
                <i class="ki-filled ki-arrow-left"></i>
                Back to Levels
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
                <h3 class="kt-card-title">Level Details</h3>
            </div>
            <div class="kt-card-content">
                <form method="POST" action="{{ route('levels.update', $level) }}" class="grid gap-7">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                        <div class="col-span-1 lg:col-span-1">
                            <label class="kt-form-label block mb-2" for="name">
                                Level Name <span class="text-destructive">*</span>
                            </label>
                            <input id="name" name="name" type="text" value="{{ old('name', $level->name) }}"
                                   class="kt-input w-full" placeholder="e.g. Regional Office" required
                                   aria-invalid="@error('name') true @else false @enderror" />
                            <div class="mt-1 text-xs text-muted-foreground">
                                Enter a descriptive name for this level.
                            </div>
                            @error('name')
                                <p class="mt-1 text-sm text-destructive">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="col-span-1 lg:col-span-1">
                            <label class="kt-form-label block mb-2" for="position">
                                Position <span class="text-destructive">*</span>
                            </label>
                            <input id="position" name="position" type="number" min="1"
                                   value="{{ old('position', $level->position) }}"
                                   class="kt-input w-full" placeholder="e.g. 1" required
                                   aria-invalid="@error('position') true @else false @enderror" />
                            <div class="mt-1 text-xs text-muted-foreground">
                                Hierarchy position (lower numbers = higher in hierarchy).
                            </div>
                            @error('position')
                                <p class="mt-1 text-sm text-destructive">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="pt-5 mt-2 flex justify-between items-center">
                        <div class="flex items-center gap-2.5">
                            <button type="submit" class="kt-btn kt-btn-primary">
                                <i class="ki-filled ki-check"></i>
                                Update Level
                            </button>
                            <a class="kt-btn kt-btn-light" href="{{ route('levels.index') }}">Cancel</a>
                        </div>
                        @can(App\Enums\Tenant\PermissionKey::AccessLevels->value)
                            <button type="button" class="kt-btn kt-btn-danger"
                                    onclick="if(confirm('Are you sure you want to delete this level? This action cannot be undone.')) { document.getElementById('delete-level-form').submit(); }">
                                <i class="ki-filled ki-trash"></i>
                                Delete Level
                            </button>
                        @endcan
                    </div>
                </form>

                @can(App\Enums\Tenant\PermissionKey::AccessLevels->value)
                    <form id="delete-level-form" action="{{ route('levels.destroy', $level) }}" method="POST" class="hidden">
                        @csrf @method('DELETE')
                    </form>
                @endcan
            </div>
        </div>
    </div>
</div>
<!-- End of Container -->
@endsection
