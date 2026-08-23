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
    <div class="sgh-card max-w-[370px] w-full">
        <div class="flex justify-center" style="padding-top: 3.5rem; padding-bottom: 1.25rem;">
            <img class="dark:hidden h-6 w-auto" src="{{ asset('assets/media/app/flowledger_logo_light.png') }}" alt="{{ config('app.name') }}" />
            <img class="hidden dark:block h-6 w-auto" src="{{ asset('assets/media/app/flowledger_logo_dark.png') }}" alt="{{ config('app.name') }}" />
        </div>
        <div class="sgh-card-content flex flex-col gap-5 p-10">
            <div class="text-center mb-2.5">
                <h3 class="text-lg font-medium text-mono leading-none mb-2.5">
                    {{ __('auth.change_password') }}
                </h3>
                <p class="text-sm text-muted-foreground">
                    {{ __('auth.change_password_hint') }}
                </p>
            </div>

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

            <form action="{{ route('password.change.update') }}" method="POST" class="flex flex-col gap-5">
                @csrf
                @method('PUT')

                <div class="flex flex-col gap-1">
                    <label class="sgh-form-label font-normal text-mono" for="password">
                        {{ __('auth.new_password') }}
                    </label>
                    <div class="sgh-input flex items-center gap-1 py-0" x-data="{ show: false }" aria-invalid="@error('password') true @else false @enderror">
                        <input
                            class="grow border-0 bg-transparent px-0 py-2 focus:outline-none focus:ring-0"
                            id="password"
                            name="password"
                            placeholder="{{ __('auth.enter_new_password') }}" required
                            autofocus
                            :type="show ? 'text' : 'password'"
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

                <div class="flex flex-col gap-1">
                    <label class="sgh-form-label font-normal text-mono" for="password_confirmation">
                        {{ __('auth.confirm_password') }}
                    </label>
                    <div class="sgh-input flex items-center gap-1 py-0" x-data="{ show: false }">
                        <input
                            class="grow border-0 bg-transparent px-0 py-2 focus:outline-none focus:ring-0"
                            id="password_confirmation"
                            name="password_confirmation"
                            placeholder="{{ __('auth.confirm_new_password') }}" required
                            :type="show ? 'text' : 'password'"
                        />
                        <button class="sgh-btn sgh-btn-sm sgh-btn-ghost sgh-btn-icon bg-transparent! -me-1.5" @click="show = !show" type="button">
                            <x-tabler-eye-filled class="text-muted-foreground" x-show="!show" />
                            <x-tabler-eye-closed class="text-muted-foreground" x-show="show" x-cloak />
                        </button>
                    </div>
                </div>

                <button class="sgh-btn sgh-btn-primary flex justify-center grow" type="submit">
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
            <div class="flex items-center gap-2" x-data="themeToggle">
                <x-tabler-moon-filled class="text-base text-muted-foreground" />
                <button type="button" role="switch" :aria-checked="dark" @click="toggleTheme()"
                    class="sgh-switch sgh-switch-sm" :class="dark ? 'bg-primary' : 'bg-muted'"
                    aria-label="{{ __('navigation.dark_mode') }}">
                    <span class="sgh-switch-thumb" :class="dark ? 'translate-x-[13px]' : 'translate-x-0.5'"></span>
                </button>
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
