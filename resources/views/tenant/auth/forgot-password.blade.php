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
    <div class="fl-card max-w-[370px] w-full">
        <div class="flex justify-center" style="padding-top: 3.5rem; padding-bottom: 1.25rem;">
            <img class="dark:hidden h-6 w-auto" src="{{ asset('assets/media/app/flowledger_logo_light.png') }}" alt="{{ config('app.name') }}" />
            <img class="hidden dark:block h-6 w-auto" src="{{ asset('assets/media/app/flowledger_logo_dark.png') }}" alt="{{ config('app.name') }}" />
        </div>
        <div class="fl-card-content flex flex-col gap-5 p-10">
            <div class="text-center mb-2.5">
                <h3 class="text-lg font-medium text-mono leading-none mb-2.5">
                    {{ __('auth.forgot_password') }}
                </h3>
                <p class="text-sm text-secondary-foreground">
                    {{ __('auth.forgot_password_hint') }}
                </p>
            </div>

            @if (session('status'))
                <div class="fl-alert fl-alert-light fl-alert-success">
                    <span class="fl-alert-icon"><x-tabler-circle-check-filled class="text-xl" /></span>
                    <div class="fl-alert-content">
                        <p class="fl-alert-description">{{ session('status') }}</p>
                    </div>
                </div>
            @endif

            @if ($errors->any())
                <div class="fl-alert fl-alert-light fl-alert-destructive">
                    <span class="fl-alert-icon"><x-tabler-info-square-filled class="text-xl" /></span>
                    <div class="fl-alert-content">
                        <ul class="fl-alert-description list-disc ps-5">
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

                <button class="fl-btn fl-btn-primary flex justify-center grow" type="submit">
                    {{ __('auth.send_reset_link') }}
                </button>
            </form>

            <div class="text-center">
                <a class="text-sm fl-link" href="{{ route('login') }}">
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
            <div class="flex items-center gap-2">
                <x-tabler-moon-filled class="text-base text-muted-foreground" />
                <input class="kt-switch kt-switch-sm" data-kt-theme-switch-state="dark" data-kt-theme-switch-toggle="true" type="checkbox" aria-label="{{ __('navigation.dark_mode') }}" />
            </div>
        </div>
    </div>
</div>
@endsection
