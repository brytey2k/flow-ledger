@extends('tenant.layouts.auth')

@section('title', __('auth.change_password'))

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
                    {{ __('auth.change_password') }}
                </h3>
                <p class="text-sm text-muted-foreground">
                    {{ __('auth.change_password_hint') }}
                </p>
            </div>

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

            <form action="{{ route('password.change.update') }}" method="POST" class="flex flex-col gap-5">
                @csrf
                @method('PUT')

                <div class="flex flex-col gap-1">
                    <label class="fl-form-label font-normal text-mono" for="password">
                        {{ __('auth.new_password') }}
                    </label>
                    <div class="fl-input" data-kt-toggle-password="true" aria-invalid="@error('password') true @else false @enderror">
                        <input
                            id="password"
                            name="password"
                            placeholder="{{ __('auth.enter_new_password') }}"
                            type="password"
                            required
                            autofocus
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

                <div class="flex flex-col gap-1">
                    <label class="fl-form-label font-normal text-mono" for="password_confirmation">
                        {{ __('auth.confirm_password') }}
                    </label>
                    <div class="fl-input" data-kt-toggle-password="true">
                        <input
                            id="password_confirmation"
                            name="password_confirmation"
                            placeholder="{{ __('auth.confirm_new_password') }}"
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
                </div>

                <button class="fl-btn fl-btn-primary flex justify-center grow" type="submit">
                    {{ __('auth.change_password') }}
                </button>
            </form>
        </div>
        <div class="border-t border-border px-10 py-4 flex items-center justify-between">
            <form method="POST" action="{{ route('locale.update') }}">
                @csrf
                <label class="sr-only" for="tenant-change-password-locale">{{ __('navigation.language') }}</label>
                <select
                    id="tenant-change-password-locale"
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
        <div class="text-center py-4">
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="text-sm text-muted-foreground hover:text-foreground underline">
                    {{ __('auth.sign_out_later') }}
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
