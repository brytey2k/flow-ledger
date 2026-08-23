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
    <div class="fl-card max-w-[370px] w-full">
        <div class="flex justify-center" style="padding-top: 3.5rem; padding-bottom: 1.25rem;">
            <img class="dark:hidden h-6 w-auto" src="{{ asset('assets/media/app/flowledger_logo_light.png') }}" alt="{{ config('app.name') }}" />
            <img class="hidden dark:block h-6 w-auto" src="{{ asset('assets/media/app/flowledger_logo_dark.png') }}" alt="{{ config('app.name') }}" />
        </div>
        <div class="fl-card-content flex flex-col gap-5 p-10">
            <div class="text-center mb-2.5">
                <h3 class="text-lg font-medium text-mono leading-none mb-2.5">
                    {{ __('auth.sign_in_heading') }}
                </h3>
            </div>

            @if ($errors->any() || request()->filled('sso_error'))
                <div class="fl-alert fl-alert-light fl-alert-destructive">
                    <span class="fl-alert-icon"><x-tabler-info-square-filled class="text-xl" /></span>
                    <div class="fl-alert-content">
                        <ul class="fl-alert-description list-disc ps-5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                            @if (request()->filled('sso_error'))
                                <li>{{ request()->query('sso_error') }}</li>
                            @endif
                        </ul>
                    </div>
                </div>
            @endif

            @if ($localAuthEnabled)
                <form action="{{ route('login') }}" id="sign_in_form" method="POST" class="contents">
                    @csrf

                    <div class="flex flex-col gap-1">
                        <label class="fl-form-label font-normal text-mono" for="email">
                            {{ __('auth.email') }}
                        </label>
                        <input
                            class="fl-input"
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
                            <label class="fl-form-label font-normal text-mono" for="password">
                                {{ __('auth.password') }}
                            </label>
                            @if (! $identityDelegated)
                                <a class="text-sm fl-link shrink-0" href="{{ route('password.request') }}">
                                    {{ __('auth.forgot_password_link') }}
                                </a>
                            @endif
                        </div>
                        <div class="fl-input" data-kt-toggle-password="true" aria-invalid="@error('password') true @else false @enderror">
                            <input
                                id="password"
                                name="password"
                                placeholder="{{ __('auth.enter_password') }}"
                                type="password"
                                required
                            />
                            <button class="fl-btn fl-btn-sm fl-btn-ghost fl-btn-icon bg-transparent! -me-1.5" data-kt-toggle-password-trigger="true" type="button">
                                <span class="kt-toggle-password-active:hidden">
                                    <x-tabler-eye-filled class="text-muted-foreground" />
                                </span>
                                <span class="hidden kt-toggle-password-active:block">
                                    <x-tabler-eye-closed class="text-muted-foreground" />
                                </span>
                            </button>
                        </div>
                        @error('password')
                            <span class="text-xs text-destructive">{{ $message }}</span>
                        @enderror
                    </div>

                    <label class="fl-label">
                        <input class="fl-checkbox fl-checkbox-sm" name="remember" type="checkbox" value="1" {{ old('remember') ? 'checked' : '' }}/>
                        <span class="fl-checkbox-label">
                            {{ __('auth.remember_me') }}
                        </span>
                    </label>

                    <button class="fl-btn fl-btn-primary flex justify-center grow" type="submit">
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

                @php
                    $ssoScheme = request()->getScheme();
                    $ssoPort = request()->getPort();
                    $ssoPortSuffix = in_array($ssoPort, [80, 443], true) ? '' : ":{$ssoPort}";
                    $ssoUrl = "{$ssoScheme}://" . config('app.central_url') . $ssoPortSuffix . '/auth/sso/redirect?return_to=' . urlencode(route('login'));
                @endphp
                <a class="fl-btn fl-btn-light flex justify-center grow gap-2" href="{{ $ssoUrl }}">
                    <x-tabler-shield-check-filled class="text-base" />
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
                <x-tabler-moon-filled class="text-base text-muted-foreground" />
                <input class="kt-switch kt-switch-sm" data-kt-theme-switch-state="dark" data-kt-theme-switch-toggle="true" type="checkbox" aria-label="{{ __('navigation.dark_mode') }}" />
            </div>
        </div>
    </div>
</div>
@endsection
