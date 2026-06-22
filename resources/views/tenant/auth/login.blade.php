@extends('tenant.layouts.auth')

@section('title', __('auth.sign_in'))

@push('styles')
<style>
    .page-bg {
        background-image: url('/assets/media/images/2600x1200/bg-10.png');
    }
    .dark .page-bg {
        background-image: url('/assets/media/images/2600x1200/bg-10-dark.png');
    }
</style>
@endpush

@section('content')
<div class="flex items-center justify-center grow bg-center bg-no-repeat page-bg">
    <div class="kt-card max-w-[370px] w-full">
        <div class="kt-card-content flex flex-col gap-5 p-10">
            <div class="text-center mb-2.5">
                <h3 class="text-lg font-medium text-mono leading-none mb-2.5">
                    {{ __('auth.sign_in_heading') }}
                </h3>
            </div>

            @if ($errors->any())
                <div class="kt-alert kt-alert-light kt-alert-destructive">
                    <span class="kt-alert-icon"><i class="ki-filled ki-information-2 text-xl"></i></span>
                    <div class="kt-alert-content">
                        <ul class="kt-alert-description list-disc ps-5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            @if ($localAuthEnabled)
                <form action="{{ route('login') }}" id="sign_in_form" method="POST" class="contents">
                    @csrf

                    <div class="flex flex-col gap-1">
                        <label class="kt-form-label font-normal text-mono" for="email">
                            {{ __('auth.email') }}
                        </label>
                        <input
                            class="kt-input"
                            id="email"
                            name="email"
                            placeholder="{{ __('auth.email_placeholder') }}"
                            type="email"
                            value="{{ old('email') }}"
                            required
                            autofocus
                            aria-invalid="@error('email') true @else false @enderror"
                        />
                        @error('email')
                            <span class="text-xs text-destructive">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="flex flex-col gap-1">
                        <div class="flex items-center justify-between gap-1">
                            <label class="kt-form-label font-normal text-mono" for="password">
                                {{ __('auth.password') }}
                            </label>
                            <a class="text-sm kt-link shrink-0" href="{{ route('password.request') }}">
                                {{ __('auth.forgot_password_link') }}
                            </a>
                        </div>
                        <div class="kt-input" data-kt-toggle-password="true" aria-invalid="@error('password') true @else false @enderror">
                            <input
                                id="password"
                                name="password"
                                placeholder="{{ __('auth.enter_password') }}"
                                type="password"
                                required
                            />
                            <button class="kt-btn kt-btn-sm kt-btn-ghost kt-btn-icon bg-transparent! -me-1.5" data-kt-toggle-password-trigger="true" type="button">
                                <span class="kt-toggle-password-active:hidden">
                                    <i class="ki-filled ki-eye text-muted-foreground"></i>
                                </span>
                                <span class="hidden kt-toggle-password-active:block">
                                    <i class="ki-filled ki-eye-slash text-muted-foreground"></i>
                                </span>
                            </button>
                        </div>
                        @error('password')
                            <span class="text-xs text-destructive">{{ $message }}</span>
                        @enderror
                    </div>

                    <label class="kt-label">
                        <input class="kt-checkbox kt-checkbox-sm" name="remember" type="checkbox" value="1" {{ old('remember') ? 'checked' : '' }}/>
                        <span class="kt-checkbox-label">
                            {{ __('auth.remember_me') }}
                        </span>
                    </label>

                    <button class="kt-btn kt-btn-primary flex justify-center grow" type="submit">
                        {{ __('auth.sign_in') }}
                    </button>
                </form>
            @endif

            @if (Route::has('sso.redirect'))
                @if ($localAuthEnabled)
                    <div class="relative flex items-center gap-3">
                        <div class="border-t border-border grow"></div>
                        <span class="text-xs text-muted-foreground shrink-0">{{ __('auth.or') }}</span>
                        <div class="border-t border-border grow"></div>
                    </div>
                @endif

                <a class="kt-btn kt-btn-light flex justify-center grow gap-2" href="{{ url('/auth/sso/redirect') }}">
                    <i class="ki-filled ki-shield-tick text-base"></i>
                    {{ __('auth.sign_in_with_sso') }}
                </a>
            @endif
        </div>
        <div class="border-t border-border px-10 py-4 flex items-center justify-between">
            <form method="POST" action="{{ route('locale.update') }}">
                @csrf
                <label class="sr-only" for="tenant-auth-locale">{{ __('navigation.language') }}</label>
                <select
                    id="tenant-auth-locale"
                    name="locale"
                    class="h-8 rounded-md border border-border bg-background px-2 text-xs text-foreground"
                    onchange="this.form.submit()"
                >
                    <option value="en" @selected(app()->getLocale() === 'en')>English</option>
                    <option value="fr" @selected(app()->getLocale() === 'fr')>Francais</option>
                </select>
            </form>
            <div class="flex items-center gap-2">
                <i class="ki-filled ki-moon text-base text-muted-foreground"></i>
                <input class="kt-switch kt-switch-sm" data-kt-theme-switch-state="dark" data-kt-theme-switch-toggle="true" type="checkbox" aria-label="{{ __('navigation.dark_mode') }}" />
            </div>
        </div>
    </div>
</div>
@endsection
