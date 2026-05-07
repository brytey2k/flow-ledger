@extends('tenant.layouts.base')

@section('content')
<!-- Container -->
<div class="kt-container-fixed">
    <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono">Edit Branch</h1>
            <div class="flex items-center gap-2 text-sm font-normal text-secondary-foreground">
                Update branch details and hierarchy
            </div>
        </div>
        <div class="flex items-center gap-2.5">
            <a class="kt-btn kt-btn-outline" href="{{ route('branches.index') }}">
                <i class="ki-filled ki-arrow-left"></i>
                Back to Branches
            </a>
        </div>
    </div>
</div>
<!-- End of Container -->

<!-- Container -->
<div class="kt-container-fixed">
    @if($descendantsCount > 0)
        <div class="kt-alert kt-alert-light kt-alert-warning mb-5">
            <span class="kt-alert-icon"><i class="ki-filled ki-information-4 text-xl"></i></span>
            <div class="kt-alert-content">
                <h4 class="kt-alert-title">Branch Has Descendants</h4>
                <div class="kt-alert-description">
                    This branch has {{ $descendantsCount }} {{ Str::plural('child branch', $descendantsCount) }}. Changing the parent may affect the hierarchy structure.
                </div>
            </div>
        </div>
    @endif

    <div class="grid gap-5 lg:gap-7.5">
        <div class="kt-card">
            <div class="kt-card-header">
                <h3 class="kt-card-title">Branch Details</h3>
            </div>
            <div class="kt-card-content">
                <form method="POST" action="{{ route('branches.update', $branch) }}" class="grid gap-7">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                        <div class="col-span-1 lg:col-span-2">
                            <label class="kt-form-label block mb-2" for="name">
                                Branch Name <span class="text-destructive">*</span>
                            </label>
                            <input id="name" name="name" type="text" value="{{ old('name', $branch->name) }}"
                                   class="kt-input w-full" placeholder="e.g. Accra Regional Office" required
                                   aria-invalid="@error('name') true @else false @enderror" />
                            <div class="mt-1 text-xs text-muted-foreground">
                                Enter a descriptive name for this branch.
                            </div>
                            @error('name')
                                <p class="mt-1 text-sm text-destructive">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="col-span-1 lg:col-span-1">
                            <label class="kt-form-label block mb-2" for="code">
                                Branch Code
                            </label>
                            <input id="code" name="code" type="text" value="{{ old('code', $branch->code) }}"
                                   class="kt-input w-full" placeholder="e.g. ACC-REG"
                                   aria-invalid="@error('code') true @else false @enderror" />
                            <div class="mt-1 text-xs text-muted-foreground">
                                Optional unique identifier for this branch.
                            </div>
                            @error('code')
                                <p class="mt-1 text-sm text-destructive">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="col-span-1 lg:col-span-1">
                            <label class="kt-form-label block mb-2" for="position">
                                Position <span class="text-destructive">*</span>
                            </label>
                            <input id="position" name="position" type="number" min="1"
                                   value="{{ old('position', $branch->position) }}"
                                   class="kt-input w-full" placeholder="e.g. 1" required
                                   aria-invalid="@error('position') true @else false @enderror" />
                            <div class="mt-1 text-xs text-muted-foreground">
                                Display order within the same level.
                            </div>
                            @error('position')
                                <p class="mt-1 text-sm text-destructive">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="col-span-1 lg:col-span-1">
                            <label class="kt-form-label block mb-2" for="level_id">
                                Level <span class="text-destructive">*</span>
                            </label>
                            <select id="level_id" name="level_id" class="kt-input w-full" required
                                    aria-invalid="@error('level_id') true @else false @enderror">
                                <option value="">Select a level…</option>
                                @foreach($levels as $level)
                                    <option value="{{ $level->id }}" {{ old('level_id', $branch->level_id) == $level->id ? 'selected' : '' }}>
                                        {{ $level->name }} (Position: {{ $level->position }})
                                    </option>
                                @endforeach
                            </select>
                            <div class="mt-1 text-xs text-muted-foreground">
                                Organisational level for this branch.
                            </div>
                            @error('level_id')
                                <p class="mt-1 text-sm text-destructive">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="col-span-1 lg:col-span-1">
                            <label class="kt-form-label block mb-2" for="currency_id">
                                Reporting Currency <span class="text-destructive">*</span>
                            </label>
                            <select id="currency_id" name="currency_id" class="kt-input w-full" required
                                    aria-invalid="@error('currency_id') true @else false @enderror">
                                <option value="">Select a currency…</option>
                                @foreach($currencies as $currency)
                                    <option value="{{ $currency->id }}" {{ old('currency_id', $branch->currency_id) == $currency->id ? 'selected' : '' }}>
                                        {{ $currency->name }} ({{ $currency->short_name }})
                                    </option>
                                @endforeach
                            </select>
                            <div class="mt-1 text-xs text-muted-foreground">
                                Currency used for reporting in this branch.
                            </div>
                            @error('currency_id')
                                <p class="mt-1 text-sm text-destructive">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="col-span-1 lg:col-span-1">
                            <label class="kt-form-label block mb-2" for="parent_id">
                                Parent Branch
                            </label>
                            <select id="parent_id" name="parent_id" class="kt-input w-full"
                                    aria-invalid="@error('parent_id') true @else false @enderror">
                                <option value="">None (Root Branch)</option>
                                @foreach($branches as $b)
                                    @php $indent = str_repeat('—', $b->depth); @endphp
                                    <option value="{{ $b->id }}" {{ old('parent_id', $branch->parent_id) == $b->id ? 'selected' : '' }}>
                                        @if($b->depth > 0){{ $indent }} @endif{{ $b->name }} ({{ $b->level->name }})
                                    </option>
                                @endforeach
                            </select>
                            <div class="mt-1 text-xs text-muted-foreground">
                                Leave empty for root branch, or select a parent.
                            </div>
                            @error('parent_id')
                                <p class="mt-1 text-sm text-destructive">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="pt-5 mt-2 flex justify-between items-center">
                        <div class="flex items-center gap-2.5">
                            <button type="submit" class="kt-btn kt-btn-primary">
                                <i class="ki-filled ki-check"></i>
                                Update Branch
                            </button>
                            <a class="kt-btn kt-btn-light" href="{{ route('branches.index') }}">Cancel</a>
                        </div>
                        @can(App\Enums\Tenant\PermissionKey::AccessBranches->value)
                            <button type="button" class="kt-btn kt-btn-danger"
                                    onclick="if(confirm('Are you sure you want to delete this branch? This action cannot be undone.')) { document.getElementById('delete-branch-form').submit(); }">
                                <i class="ki-filled ki-trash"></i>
                                Delete Branch
                            </button>
                        @endcan
                    </div>
                </form>

                @can(App\Enums\Tenant\PermissionKey::AccessBranches->value)
                    <form id="delete-branch-form" action="{{ route('branches.destroy', $branch) }}" method="POST" class="hidden">
                        @csrf @method('DELETE')
                    </form>
                @endcan
            </div>
        </div>
    </div>
</div>
<!-- End of Container -->
@endsection
