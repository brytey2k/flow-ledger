@extends('tenant.layouts.auth')

@section('title', __('auth.forgot_password'))

@section('content')
<div class="mb-7 flex items-center justify-end gap-2">
    <form method="POST" action="{{ route('locale.update') }}">
        @csrf
        <label class="sr-only" for="tenant-forgot-locale">{{ __('navigation.language') }}</label>
        <select
            id="tenant-forgot-locale"
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

<h1 class="mb-2 text-[27px] font-semibold tracking-tight text-foreground">{{ __('auth.forgot_password') }}</h1>
<p class="mb-8 text-[14.5px] leading-relaxed text-muted-foreground">{{ __('auth.forgot_password_hint') }}</p>

@if (session('status'))
    <div class="sgh-alert sgh-alert-success mb-5">
        <x-tabler-circle-check-filled class="size-4" />
        <p>{{ session('status') }}</p>
    </div>
@endif

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

<form action="{{ route('password.email') }}" method="POST" class="flex flex-col gap-5">
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

    <button class="sgh-btn sgh-btn-primary flex justify-center grow" type="submit">
        {{ __('auth.send_reset_link') }}
    </button>
</form>

<div class="mt-6">
    <a class="text-sm sgh-link" href="{{ route('login') }}">
        {{ __('auth.back_to_sign_in') }}
    </a>
</div>
@endsection
