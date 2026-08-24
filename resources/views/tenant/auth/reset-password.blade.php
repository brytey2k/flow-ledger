@extends('tenant.layouts.auth')

@section('title', __('auth.reset_password'))

@section('content')
<div class="mb-7 flex items-center justify-end gap-2">
    <form method="POST" action="{{ route('locale.update') }}">
        @csrf
        <label class="sr-only" for="tenant-reset-locale">{{ __('navigation.language') }}</label>
        <select
            id="tenant-reset-locale"
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

<h1 class="mb-2 text-[27px] font-semibold tracking-tight text-foreground">{{ __('auth.reset_password') }}</h1>
<p class="mb-8 text-[14.5px] leading-relaxed text-muted-foreground">{{ __('auth.reset_password_subtitle') }}</p>

@if ($errors->any())
    <div class="sgh-alert sgh-alert-danger mb-5">
        <x-tabler-info-circle-filled class="size-4" />
        <ul class="list-disc list-inside">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form action="{{ route('password.update') }}" method="POST" class="flex flex-col gap-5">
    @csrf

    <input type="hidden" name="token" value="{{ $token }}">

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
            value="{{ old('email', $email) }}"
            required
            autofocus
            aria-invalid="@error('email') true @else false @enderror"
        />
        @error('email')
            <span class="text-xs text-destructive">{{ $message }}</span>
        @enderror
    </div>

    <div class="flex flex-col gap-1" x-data="{ show: false }">
        <label class="sgh-label" for="password">
            {{ __('auth.new_password') }}
        </label>
        <div class="sgh-input flex items-center gap-1 py-0" aria-invalid="@error('password') true @else false @enderror">
            <input
                class="grow border-0 bg-transparent px-0 py-2 focus:outline-none focus:ring-0"
                id="password"
                name="password"
                placeholder="{{ __('auth.enter_new_password') }}"
                :type="show ? 'text' : 'password'"
                required
            />
            <button class="sgh-btn sgh-btn-ghost sgh-btn-icon sgh-btn-sm -me-1.5" @click="show = !show" type="button" aria-label="{{ __('auth.new_password') }}">
                <x-tabler-eye-filled class="text-muted-foreground" x-show="!show" />
                <x-tabler-eye-closed class="text-muted-foreground" x-show="show" x-cloak />
            </button>
        </div>
        @error('password')
            <span class="text-xs text-destructive">{{ $message }}</span>
        @enderror
    </div>

    <div class="flex flex-col gap-1" x-data="{ show: false }">
        <label class="sgh-label" for="password_confirmation">
            {{ __('auth.confirm_password') }}
        </label>
        <div class="sgh-input flex items-center gap-1 py-0">
            <input
                class="grow border-0 bg-transparent px-0 py-2 focus:outline-none focus:ring-0"
                id="password_confirmation"
                name="password_confirmation"
                placeholder="{{ __('auth.confirm_new_password') }}"
                :type="show ? 'text' : 'password'"
                required
            />
            <button class="sgh-btn sgh-btn-ghost sgh-btn-icon sgh-btn-sm -me-1.5" @click="show = !show" type="button" aria-label="{{ __('auth.confirm_password') }}">
                <x-tabler-eye-filled class="text-muted-foreground" x-show="!show" />
                <x-tabler-eye-closed class="text-muted-foreground" x-show="show" x-cloak />
            </button>
        </div>
    </div>

    <button class="sgh-btn sgh-btn-primary flex justify-center grow" type="submit">
        {{ __('auth.reset_password') }}
    </button>
</form>

<div class="mt-6">
    <a class="text-sm sgh-link" href="{{ route('login') }}">
        {{ __('auth.back_to_sign_in') }}
    </a>
</div>
@endsection
