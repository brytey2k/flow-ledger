@extends('tenant.layouts.base')

@section('content')
<div class="kt-container-fixed">
    <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono">Add Staff Member</h1>
            <div class="flex items-center gap-2 text-sm font-normal text-secondary-foreground">
                Add a new staff member to your organisation
            </div>
        </div>
        <div class="flex items-center gap-2.5">
            <a class="kt-btn kt-btn-outline" href="{{ route('staff.index') }}">
                <i class="ki-filled ki-arrow-left"></i>
                Back to Staff
            </a>
        </div>
    </div>
</div>

<div class="kt-container-fixed">
    <div class="grid gap-5 lg:gap-7.5">
        <div class="kt-card">
            <div class="kt-card-header">
                <h3 class="kt-card-title">Staff Details</h3>
            </div>
            <div class="kt-card-content">
                <form method="POST" action="{{ route('staff.store') }}" class="grid gap-7">
                    @csrf

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                        <div class="col-span-1">
                            <label class="kt-form-label block mb-2" for="first_name">
                                First Name <span class="text-destructive">*</span>
                            </label>
                            <input id="first_name" name="first_name" type="text" value="{{ old('first_name') }}"
                                   class="kt-input w-full" placeholder="e.g. John" required
                                   aria-invalid="@error('first_name') true @else false @enderror" />
                            @error('first_name')
                                <p class="mt-1 text-sm text-destructive">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="col-span-1">
                            <label class="kt-form-label block mb-2" for="last_name">
                                Last Name <span class="text-destructive">*</span>
                            </label>
                            <input id="last_name" name="last_name" type="text" value="{{ old('last_name') }}"
                                   class="kt-input w-full" placeholder="e.g. Doe" required
                                   aria-invalid="@error('last_name') true @else false @enderror" />
                            @error('last_name')
                                <p class="mt-1 text-sm text-destructive">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="col-span-1">
                            <label class="kt-form-label block mb-2" for="email">
                                Email
                            </label>
                            <input id="email" name="email" type="email" value="{{ old('email') }}"
                                   class="kt-input w-full" placeholder="e.g. john.doe@example.com"
                                   aria-invalid="@error('email') true @else false @enderror" />
                            @error('email')
                                <p class="mt-1 text-sm text-destructive">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="col-span-1">
                            <label class="kt-form-label block mb-2" for="phone">
                                Phone
                            </label>
                            <input id="phone" name="phone" type="text" value="{{ old('phone') }}"
                                   class="kt-input w-full" placeholder="e.g. +1 234 567 8900"
                                   aria-invalid="@error('phone') true @else false @enderror" />
                            @error('phone')
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
                                    <option value="{{ $department->id }}" {{ old('department_id') == $department->id ? 'selected' : '' }}>
                                        {{ $department->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('department_id')
                                <p class="mt-1 text-sm text-destructive">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="col-span-1">
                            <label class="kt-form-label block mb-2" for="position_id">
                                Position <span class="text-destructive">*</span>
                            </label>
                            <select id="position_id" name="position_id" class="kt-select w-full" required
                                    aria-invalid="@error('position_id') true @else false @enderror">
                                <option value="">Select a position</option>
                                @foreach($positions as $position)
                                    <option value="{{ $position->id }}" {{ old('position_id') == $position->id ? 'selected' : '' }}>
                                        {{ $position->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('position_id')
                                <p class="mt-1 text-sm text-destructive">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="pt-5 mt-2 flex justify-start items-center gap-2.5">
                        <button type="submit" class="kt-btn kt-btn-primary">
                            <i class="ki-filled ki-plus"></i>
                            Add Staff Member
                        </button>
                        <a class="kt-btn kt-btn-light" href="{{ route('staff.index') }}">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
