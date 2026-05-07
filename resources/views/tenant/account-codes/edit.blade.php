@extends('tenant.layouts.base')

@section('content')
<!-- Container -->
<div class="kt-container-fixed">
    <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono">Edit Account Code</h1>
            <div class="flex items-center gap-2 text-sm font-normal text-secondary-foreground">
                Update account code details
            </div>
        </div>
        <div class="flex items-center gap-2.5">
            <a class="kt-btn kt-btn-outline" href="{{ route('account-codes.index') }}">
                <i class="ki-filled ki-arrow-left"></i>
                Back to Account Codes
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
                <h3 class="kt-card-title">Account Code Details</h3>
            </div>
            <div class="kt-card-content">
                <form method="POST" action="{{ route('account-codes.update', $accountCode) }}" class="grid gap-7">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                        <div class="col-span-1">
                            <label class="kt-form-label block mb-2" for="code">
                                Code <span class="text-destructive">*</span>
                            </label>
                            <input id="code" name="code" type="text" value="{{ old('code', $accountCode->code) }}"
                                   class="kt-input w-full" placeholder="e.g. ACC-1001" required
                                   aria-invalid="@error('code') true @else false @enderror" />
                            <div class="mt-1 text-xs text-muted-foreground">
                                A unique identifier for this account code.
                            </div>
                            @error('code')
                                <p class="mt-1 text-sm text-destructive">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="col-span-1">
                            <label class="kt-form-label block mb-2" for="name">
                                Name <span class="text-destructive">*</span>
                            </label>
                            <input id="name" name="name" type="text" value="{{ old('name', $accountCode->name) }}"
                                   class="kt-input w-full" placeholder="e.g. Office Supplies" required
                                   aria-invalid="@error('name') true @else false @enderror" />
                            <div class="mt-1 text-xs text-muted-foreground">
                                A descriptive name for this account code.
                            </div>
                            @error('name')
                                <p class="mt-1 text-sm text-destructive">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="col-span-1">
                            <label class="kt-form-label block mb-2" for="department_id">
                                Department <span class="text-destructive">*</span>
                            </label>
                            <select id="department_id" name="department_id" class="kt-select w-full" required
                                    aria-invalid="@error('department_id') true @else false @enderror">
                                <option value="">Select a department</option>
                                @foreach($departments as $department)
                                    <option value="{{ $department->id }}" {{ old('department_id', $accountCode->department_id) == $department->id ? 'selected' : '' }}>
                                        {{ $department->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('department_id')
                                <p class="mt-1 text-sm text-destructive">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="pt-5 mt-2 flex justify-between items-center">
                        <div class="flex items-center gap-2.5">
                            <button type="submit" class="kt-btn kt-btn-primary">
                                <i class="ki-filled ki-check"></i>
                                Update Account Code
                            </button>
                            <a class="kt-btn kt-btn-light" href="{{ route('account-codes.index') }}">Cancel</a>
                        </div>
                        @can(App\Enums\Tenant\PermissionKey::DeleteAccountCode->value)
                            <button type="button" class="kt-btn kt-btn-danger"
                                    onclick="if(confirm('Are you sure you want to delete this account code? This action cannot be undone.')) { document.getElementById('delete-account-code-form').submit(); }">
                                <i class="ki-filled ki-trash"></i>
                                Delete Account Code
                            </button>
                        @endcan
                    </div>
                </form>

                @can(App\Enums\Tenant\PermissionKey::DeleteAccountCode->value)
                    <form id="delete-account-code-form" action="{{ route('account-codes.destroy', $accountCode) }}" method="POST" class="hidden">
                        @csrf @method('DELETE')
                    </form>
                @endcan
            </div>
        </div>
    </div>
</div>
<!-- End of Container -->
@endsection
