@extends('tenant.layouts.base')

@section('content')
<!-- Container -->
<div class="kt-container-fixed">
    <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono">{{ __('users.create_title') }}</h1>
            <div class="flex items-center gap-2 text-sm font-normal text-secondary-foreground">
                {{ __('users.create_subtitle') }}
            </div>
        </div>
        <div class="flex items-center gap-2.5">
            <a class="kt-btn kt-btn-outline" href="{{ route('users.index') }}">
                <i class="ki-filled ki-arrow-left"></i>
                {{ __('users.back') }}
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
                <h3 class="kt-card-title">{{ __('users.details_card') }}</h3>
            </div>
            <div class="kt-card-content">
                <form method="POST" action="{{ route('users.store') }}" class="grid gap-7">
                    @csrf

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                        <!-- First Name -->
                        <div>
                            <label class="kt-form-label block mb-2" for="first_name">
                                {{ __('users.fields.first_name') }} <span class="text-destructive">*</span>
                            </label>
                            <input id="first_name" name="first_name" type="text" value="{{ old('first_name') }}" class="kt-input w-full" placeholder="e.g. John" required aria-invalid="@error('first_name') true @else false @enderror" />
                            @error('first_name')
                                <p class="mt-1 text-sm text-destructive">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Last Name -->
                        <div>
                            <label class="kt-form-label block mb-2" for="last_name">
                                {{ __('users.fields.last_name') }} <span class="text-destructive">*</span>
                            </label>
                            <input id="last_name" name="last_name" type="text" value="{{ old('last_name') }}" class="kt-input w-full" placeholder="e.g. Doe" required aria-invalid="@error('last_name') true @else false @enderror" />
                            @error('last_name')
                                <p class="mt-1 text-sm text-destructive">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Email -->
                        <div>
                            <label class="kt-form-label block mb-2" for="email">
                                {{ __('users.fields.email') }} <span class="text-destructive">*</span>
                            </label>
                            <input id="email" name="email" type="email" value="{{ old('email') }}" class="kt-input w-full" placeholder="e.g. john@example.com" required aria-invalid="@error('email') true @else false @enderror" />
                            @error('email')
                                <p class="mt-1 text-sm text-destructive">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Branch -->
                        <div>
                            <label class="kt-form-label block mb-2" for="branch_id">
                                {{ __('users.fields.branch') }} <span class="text-destructive">*</span>
                            </label>
                            <select id="branch_id" name="branch_id" class="kt-select w-full" required aria-invalid="@error('branch_id') true @else false @enderror">
                                <option value="">{{ __('users.fields.select_branch') }}</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}" {{ old('branch_id') == $branch->id ? 'selected' : '' }}>
                                        {{ $branch->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('branch_id')
                                <p class="mt-1 text-sm text-destructive">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Operational Branch -->
                        <div>
                            <label class="kt-form-label block mb-2" for="operational_branch_id">
                                {{ __('users.fields.operational_branch') }}
                            </label>
                            <select id="operational_branch_id" name="operational_branch_id" class="kt-select w-full" aria-invalid="@error('operational_branch_id') true @else false @enderror">
                                <option value="">{{ __('users.fields.select_operational_branch') }}</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}" {{ old('operational_branch_id') == $branch->id ? 'selected' : '' }}>
                                        {{ $branch->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('operational_branch_id')
                                <p class="mt-1 text-sm text-destructive">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Password -->
                        <div>
                            <label class="kt-form-label block mb-2" for="password">
                                {{ __('users.fields.password') }} <span class="text-destructive">*</span>
                            </label>
                            <input id="password" name="password" type="password" class="kt-input w-full" required aria-invalid="@error('password') true @else false @enderror" />
                            @error('password')
                                <p class="mt-1 text-sm text-destructive">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Password Confirmation -->
                        <div>
                            <label class="kt-form-label block mb-2" for="password_confirmation">
                                {{ __('users.fields.confirm_password') }} <span class="text-destructive">*</span>
                            </label>
                            <input id="password_confirmation" name="password_confirmation" type="password" class="kt-input w-full" required />
                        </div>

                        <!-- Roles -->
                        <div class="col-span-1 lg:col-span-2">
                            <label class="kt-form-label block mb-2">{{ __('users.fields.roles') }}</label>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                                @forelse($roles as $role)
                                    <div class="flex items-start gap-2">
                                        <input
                                            type="checkbox"
                                            id="role-{{ $role->id }}"
                                            name="roles[]"
                                            value="{{ $role->id }}"
                                            {{ in_array($role->id, old('roles', [])) ? 'checked' : '' }}
                                            class="mt-1"
                                        />
                                        <label for="role-{{ $role->id }}" class="text-sm cursor-pointer">
                                            {{ $role->name }}
                                        </label>
                                    </div>
                                @empty
                                    <p class="text-sm text-muted-foreground col-span-full">{{ __('users.fields.no_roles') }}</p>
                                @endforelse
                            </div>
                            @error('roles')
                                <p class="mt-1 text-sm text-destructive">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="pt-5 mt-2 flex justify-start items-center gap-2.5">
                        <button type="submit" class="kt-btn kt-btn-primary">
                            <i class="ki-filled ki-plus"></i>
                            {{ __('users.buttons.create') }}
                        </button>
                        <a class="kt-btn kt-btn-light" href="{{ route('users.index') }}">{{ __('common.cancel') }}</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<!-- End of Container -->
@endsection
