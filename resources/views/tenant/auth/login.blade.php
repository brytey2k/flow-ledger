@extends('tenant.layouts.auth')

@section('title', __('auth.sign_in'))

@section('content')
<div class="mb-7 flex items-center justify-end gap-2">
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
            <option value="fr" @selected(app()->getLocale() === 'fr')>Français</option>
        </select>
    </form>
    <div class="flex items-center gap-2" x-data="themeToggle">
        <button type="button" role="switch" :aria-checked="dark" @click="toggleTheme()"
                class="sgh-switch" :class="dark ? 'bg-primary' : 'bg-muted'"
                aria-label="{{ __('navigation.dark_mode') }}">
            <span class="sgh-switch-thumb" :class="dark ? 'translate-x-[18px]' : 'translate-x-0.5'"></span>
        </button>
    </div>
</div>

<h1 class="mb-2 text-[27px] font-semibold tracking-tight text-foreground">{{ __('auth.sign_in_heading') }}</h1>
<p class="mb-8 text-[14.5px] leading-relaxed text-muted-foreground">{{ __('auth.sign_in_subtitle') }}</p>

@if ($errors->any() || request()->filled('sso_error'))
    <div class="mb-5">
        <div class="sgh-alert sgh-alert-danger">
            <x-tabler-info-circle-filled class="size-4" />
            <ul class="list-disc list-inside">
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
    <form action="{{ route('login') }}" class="flex flex-col gap-5" id="sign_in_form" method="POST">
        @csrf

        <div class="flex flex-col gap-1">
            <label class="sgh-label" for="email">
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

        <div class="flex flex-col gap-1" x-data="{ show: false }">
            <div class="flex items-center justify-between gap-1">
                <label class="sgh-label" for="password">
                    {{ __('auth.password') }}
                </label>
                @if (! $identityDelegated)
                    <a class="text-sm sgh-link shrink-0" href="{{ route('password.request') }}">
                        {{ __('auth.forgot_password_link') }}
                    </a>
                @endif
            </div>
            <div class="sgh-input flex items-center gap-1 py-0" aria-invalid="@error('password') true @else false @enderror">
                <input
                    class="grow border-0 bg-transparent px-0 py-2 focus:outline-none focus:ring-0"
                    id="password"
                    name="password"
                    placeholder="{{ __('auth.enter_password') }}"
                    :type="show ? 'text' : 'password'"
                    required
                />
                <button class="sgh-btn sgh-btn-ghost sgh-btn-icon sgh-btn-sm -me-1.5" @click="show = !show" type="button" aria-label="{{ __('auth.password') }}">
                    <x-tabler-eye-filled class="text-muted-foreground" x-show="!show" />
                    <x-tabler-eye-closed class="text-muted-foreground" x-show="show" x-cloak />
                </button>
            </div>
            @error('password')
                <span class="text-xs text-destructive">{{ $message }}</span>
            @enderror
        </div>

        <label class="flex items-center gap-2 cursor-pointer">
            <input class="sgh-checkbox" name="remember" type="checkbox" value="1" {{ old('remember') ? 'checked' : '' }}/>
            <span class="text-sm text-foreground">
                {{ __('auth.remember_me') }}
            </span>
        </label>

        <button class="sgh-btn sgh-btn-primary flex justify-center grow" type="submit">
            {{ __('auth.sign_in') }}
        </button>
    </form>
@endif

@if (Route::has('sso.redirect'))
    <div class="mt-6 flex flex-col gap-3">
        @if ($localAuthEnabled)
            <div class="flex items-center gap-3">
                <span class="border-t border-border flex-grow"></span>
                <span class="text-xs text-muted-foreground">{{ __('auth.or') }}</span>
                <span class="border-t border-border flex-grow"></span>
            </div>
        @endif

        @php
            $ssoScheme = request()->getScheme();
            $ssoPort = request()->getPort();
            $ssoPortSuffix = in_array($ssoPort, [80, 443], true) ? '' : ":{$ssoPort}";
            $ssoUrl = "{$ssoScheme}://" . config('app.central_url') . $ssoPortSuffix . '/auth/sso/redirect?return_to=' . urlencode(route('login'));
        @endphp
        <a class="sgh-btn sgh-btn-outline flex justify-center gap-2" href="{{ $ssoUrl }}">
            <x-tabler-shield-check-filled />
            {{ __('auth.sign_in_with_sso') }}
        </a>
    </div>
@endif
@endsection
