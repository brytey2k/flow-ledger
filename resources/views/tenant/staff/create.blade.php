@extends('tenant.layouts.base')

@section('content')
<div class="kt-container-fixed">
    <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono">{{ __('staff.create_title') }}</h1>
            <div class="flex items-center gap-2 text-sm font-normal text-secondary-foreground">
                {{ __('staff.create_subtitle') }}
            </div>
        </div>
        <div class="flex items-center gap-2.5">
            <a class="kt-btn kt-btn-outline" href="{{ route('staff.index') }}">
                <i class="ki-filled ki-arrow-left"></i>
                {{ __('staff.back') }}
            </a>
        </div>
    </div>
</div>

<div class="kt-container-fixed">
    <form method="POST" action="{{ route('staff.store') }}" class="grid gap-5 lg:gap-7.5">
        @csrf

        {{-- Staff Details --}}
        <div class="kt-card">
            <div class="kt-card-header">
                <h3 class="kt-card-title">{{ __('staff.details_card') }}</h3>
            </div>
            <div class="kt-card-content">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                    <div class="col-span-1">
                        <label class="kt-form-label block mb-2" for="first_name">
                            {{ __('staff.fields.first_name') }} <span class="text-destructive">*</span>
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
                            {{ __('staff.fields.last_name') }} <span class="text-destructive">*</span>
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
                            {{ __('staff.fields.email') }}
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
                            {{ __('staff.fields.phone') }}
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
                            {{ __('staff.fields.department') }} <span class="text-destructive">*</span>
                        </label>
                        <select id="department_id" name="department_id" class="kt-select w-full" required
                                aria-invalid="@error('department_id') true @else false @enderror">
                            <option value="">{{ __('staff.fields.select_department') }}</option>
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
                            {{ __('staff.fields.position') }} <span class="text-destructive">*</span>
                        </label>
                        <select id="position_id" name="position_id" class="kt-select w-full" required
                                aria-invalid="@error('position_id') true @else false @enderror">
                            <option value="">{{ __('staff.fields.select_position') }}</option>
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

                    <div class="col-span-1">
                        <label class="kt-form-label block mb-2" for="branch_id">
                            {{ __('staff.fields.branch') }}
                        </label>
                        <select id="branch_id" name="branch_id" class="kt-select w-full"
                                aria-invalid="@error('branch_id') true @else false @enderror">
                            <option value="">{{ __('staff.fields.select_branch') }}</option>
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
                </div>
            </div>
        </div>

        {{-- Login Access (optional) --}}
        <div class="kt-card">
            <div class="kt-card-header">
                <h3 class="kt-card-title">{{ __('staff.fields.login_access_card') }}</h3>
            </div>
            <div class="kt-card-content grid gap-5">
                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="checkbox" id="create_user_toggle" class="kt-checkbox"
                           {{ old('user_action') ? 'checked' : '' }} />
                    <span class="kt-form-label mb-0">{{ __('staff.fields.create_user_toggle') }}</span>
                </label>
                <p class="text-sm text-muted-foreground -mt-3">{{ __('staff.fields.create_user_hint') }}</p>

                <div id="create_user_fields" class="mt-2 pt-2 border-t border-border grid gap-5"
                     style="{{ old('user_action') ? '' : 'display:none' }}">
                    {{-- Action selector --}}
                    <div class="flex gap-4">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" id="user_action_create" name="user_action" value="create" class="kt-radio"
                                   {{ old('user_action', 'create') === 'create' ? 'checked' : '' }} />
                            <span class="kt-form-label mb-0">{{ __('staff.fields.user_action_create') }}</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" id="user_action_link" name="user_action" value="link" class="kt-radio"
                                   {{ old('user_action') === 'link' ? 'checked' : '' }} />
                            <span class="kt-form-label mb-0">{{ __('staff.fields.user_action_link') }}</span>
                        </label>
                    </div>

                    {{-- Create new account --}}
                    <div id="user_action_create_fields" class="grid grid-cols-1 lg:grid-cols-2 gap-5"
                         style="{{ old('user_action') === 'link' ? 'display:none' : '' }}">
                        <div class="col-span-1">
                            <label class="kt-form-label block mb-2" for="user_email">
                                {{ __('staff.fields.user_email') }} <span class="text-destructive">*</span>
                            </label>
                            <input id="user_email" name="user_email" type="email" value="{{ old('user_email') }}"
                                   class="kt-input w-full" placeholder="e.g. john.doe@example.com"
                                   aria-invalid="@error('user_email') true @else false @enderror" />
                            @error('user_email')
                                <p class="mt-1 text-sm text-destructive">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="col-span-1">
                            <label class="kt-form-label block mb-2" for="user_roles">
                                {{ __('staff.fields.user_roles') }}
                            </label>
                            <select id="user_roles" name="user_roles[]" class="kt-select w-full" multiple
                                    aria-invalid="@error('user_roles') true @else false @enderror">
                                @foreach($roles as $role)
                                    <option value="{{ $role->id }}" {{ in_array($role->id, (array) old('user_roles', [])) ? 'selected' : '' }}>
                                        {{ $role->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('user_roles')
                                <p class="mt-1 text-sm text-destructive">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="col-span-1">
                            <label class="kt-form-label block mb-2" for="user_password">
                                {{ __('staff.fields.user_password') }} <span class="text-destructive">*</span>
                            </label>
                            <input id="user_password" name="user_password" type="password"
                                   class="kt-input w-full"
                                   aria-invalid="@error('user_password') true @else false @enderror" />
                            @error('user_password')
                                <p class="mt-1 text-sm text-destructive">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="col-span-1">
                            <label class="kt-form-label block mb-2" for="user_password_confirmation">
                                {{ __('staff.fields.user_password_confirmation') }} <span class="text-destructive">*</span>
                            </label>
                            <input id="user_password_confirmation" name="user_password_confirmation" type="password"
                                   class="kt-input w-full" />
                        </div>
                    </div>

                    {{-- Link existing account --}}
                    <div id="user_action_link_fields" style="{{ old('user_action') === 'link' ? '' : 'display:none' }}">
                        <label class="kt-form-label block mb-2" for="user_id">
                            {{ __('staff.fields.link_user') }} <span class="text-destructive">*</span>
                        </label>
                        <select id="user_id" name="user_id" class="kt-select w-full"
                                aria-invalid="@error('user_id') true @else false @enderror">
                            <option value="">{{ __('staff.fields.select_user') }}</option>
                            @foreach($unlinkedUsers as $u)
                                <option value="{{ $u->id }}" {{ old('user_id') == $u->id ? 'selected' : '' }}>
                                    {{ $u->name }} — {{ $u->email }}
                                </option>
                            @endforeach
                        </select>
                        @error('user_id')
                            <p class="mt-1 text-sm text-destructive">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="flex justify-start items-center gap-2.5 pb-5">
            <button type="submit" class="kt-btn kt-btn-primary">
                <i class="ki-filled ki-plus"></i>
                {{ __('staff.buttons.create') }}
            </button>
            <a class="kt-btn kt-btn-light" href="{{ route('staff.index') }}">{{ __('common.cancel') }}</a>
        </div>
    </form>
</div>

@push('page_js')
<script>
    (function () {
        var toggle = document.getElementById('create_user_toggle');
        var fields = document.getElementById('create_user_fields');
        var createFields = document.getElementById('user_action_create_fields');
        var linkFields = document.getElementById('user_action_link_fields');
        var radioCreate = document.getElementById('user_action_create');
        var radioLink = document.getElementById('user_action_link');

        toggle.addEventListener('change', function () {
            fields.style.display = this.checked ? '' : 'none';
        });

        function switchAction() {
            var isLink = radioLink.checked;
            createFields.style.display = isLink ? 'none' : '';
            linkFields.style.display = isLink ? '' : 'none';
        }

        radioCreate.addEventListener('change', switchAction);
        radioLink.addEventListener('change', switchAction);
    })();
</script>
@endpush
@endsection
