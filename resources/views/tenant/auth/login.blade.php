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
    <div class="sgh-card max-w-[370px] w-full">
        <div class="flex justify-center" style="padding-top: 3.5rem; padding-bottom: 1.25rem;">
            <img class="dark:hidden h-6 w-auto" src="{{ asset('assets/media/app/flowledger_logo_light.png') }}" alt="{{ config('app.name') }}" />
            <img class="hidden dark:block h-6 w-auto" src="{{ asset('assets/media/app/flowledger_logo_dark.png') }}" alt="{{ config('app.name') }}" />
        </div>
        <div class="sgh-card-content flex flex-col gap-5 p-10">
            <div class="text-center mb-2.5">
                <h3 class="text-lg font-medium text-mono leading-none mb-2.5">
                    {{ __('auth.sign_in_heading') }}
                </h3>
            </div>

            @if ($errors->any() || request()->filled('sso_error'))
                <div class="sgh-alert sgh-alert-light sgh-alert-destructive">
                    <span class="sgh-alert-icon"><x-tabler-info-square-filled class="text-xl" /></span>
                    <div class="sgh-alert-content">
                        <ul class="sgh-alert-description list-disc ps-5">
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
                        <label class="sgh-form-label font-normal text-mono" for="email">
                            {{ __('auth.email') }}
                        </label>
                        <input
                            class="sgh-input"
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
                            <label class="sgh-form-label font-normal text-mono" for="password">
                                {{ __('auth.password') }}
                            </label>
                            @if (! $identityDelegated)
                                <a class="text-sm sgh-link shrink-0" href="{{ route('password.request') }}">
                                    {{ __('auth.forgot_password_link') }}
                                </a>
                            @endif
                        </div>
                        <div class="sgh-input flex items-center gap-1 py-0" x-data="{ show: false }" aria-invalid="@error('password') true @else false @enderror">
                            <input
                                class="grow border-0 bg-transparent px-0 py-2 focus:outline-none focus:ring-0"
                                id="password"
                                name="password"
                                placeholder="{{ __('auth.enter_password') }}"
                                :type="show ? 'text' : 'password'"
                                required
                            />
                            <button class="sgh-btn sgh-btn-sm sgh-btn-ghost sgh-btn-icon bg-transparent! -me-1.5" @click="show = !show" type="button">
                                <x-tabler-eye-filled class="text-muted-foreground" x-show="!show" />
                                <x-tabler-eye-closed class="text-muted-foreground" x-show="show" x-cloak />
                            </button>
                        </div>
                        @error('password')
                            <span class="text-xs text-destructive">{{ $message }}</span>
                        @enderror
                    </div>

                    <label class="sgh-label">
                        <input class="sgh-checkbox sgh-checkbox-sm" name="remember" type="checkbox" value="1" {{ old('remember') ? 'checked' : '' }}/>
                        <span class="sgh-checkbox-label">
                            {{ __('auth.remember_me') }}
                        </span>
                    </label>

                    <button class="sgh-btn sgh-btn-primary flex justify-center grow" type="submit">
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
                <a class="sgh-btn sgh-btn-light flex justify-center grow gap-2" href="{{ $ssoUrl }}">
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
            <div class="flex items-center gap-2" x-data="themeToggle">
                <x-tabler-moon-filled class="text-base text-muted-foreground" />
                <button type="button" role="switch" :aria-checked="dark" @click="toggleTheme()"
                    class="sgh-switch sgh-switch-sm" :class="dark ? 'bg-primary' : 'bg-muted'"
                    aria-label="{{ __('navigation.dark_mode') }}">
                    <span class="sgh-switch-thumb" :class="dark ? 'translate-x-[13px]' : 'translate-x-0.5'"></span>
                </button>
            </div>
        </div>
    </div>
</div>
@endsection
