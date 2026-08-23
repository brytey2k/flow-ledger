@extends('tenant.layouts.auth')

@section('title', __('auth.forgot_password'))

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
                    {{ __('auth.forgot_password') }}
                </h3>
                <p class="text-sm text-secondary-foreground">
                    {{ __('auth.forgot_password_hint') }}
                </p>
            </div>

            @if (session('status'))
                <div class="sgh-alert sgh-alert-light sgh-alert-success">
                    <span class="sgh-alert-icon"><x-tabler-circle-check-filled class="text-xl" /></span>
                    <div class="sgh-alert-content">
                        <p class="sgh-alert-description">{{ session('status') }}</p>
                    </div>
                </div>
            @endif

            @if ($errors->any())
                <div class="sgh-alert sgh-alert-light sgh-alert-destructive">
                    <span class="sgh-alert-icon"><x-tabler-info-square-filled class="text-xl" /></span>
                    <div class="sgh-alert-content">
                        <ul class="sgh-alert-description list-disc ps-5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            <form action="{{ route('password.email') }}" method="POST" class="flex flex-col gap-5">
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

                <button class="sgh-btn sgh-btn-primary flex justify-center grow" type="submit">
                    {{ __('auth.send_reset_link') }}
                </button>
            </form>

            <div class="text-center">
                <a class="text-sm sgh-link" href="{{ route('login') }}">
                    {{ __('auth.back_to_sign_in') }}
                </a>
            </div>
        </div>
        <div class="border-t border-border px-10 py-4 flex items-center justify-between">
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
