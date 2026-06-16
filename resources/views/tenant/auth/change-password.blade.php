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
    <div class="kt-card max-w-[370px] w-full">
        <div class="kt-card-content flex flex-col gap-5 p-10">
            <div class="text-center mb-2.5">
                <h3 class="text-lg font-medium text-mono leading-none mb-2.5">
                    {{ __('auth.change_password') }}
                </h3>
                <p class="text-sm text-muted-foreground">
                    {{ __('auth.change_password_hint') }}
                </p>
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

            <form action="{{ route('password.change.update') }}" method="POST" class="flex flex-col gap-5">
                @csrf
                @method('PUT')

                <div class="flex flex-col gap-1">
                    <label class="kt-form-label font-normal text-mono" for="password">
                        {{ __('auth.new_password') }}
                    </label>
                    <div class="kt-input" data-kt-toggle-password="true" aria-invalid="@error('password') true @else false @enderror">
                        <input
                            id="password"
                            name="password"
                            placeholder="{{ __('auth.enter_new_password') }}"
                            type="password"
                            required
                            autofocus
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

                <div class="flex flex-col gap-1">
                    <label class="kt-form-label font-normal text-mono" for="password_confirmation">
                        {{ __('auth.confirm_password') }}
                    </label>
                    <div class="kt-input" data-kt-toggle-password="true">
                        <input
                            id="password_confirmation"
                            name="password_confirmation"
                            placeholder="{{ __('auth.confirm_new_password') }}"
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
                </div>

                <button class="kt-btn kt-btn-primary flex justify-center grow" type="submit">
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
                <i class="ki-filled ki-moon text-base text-muted-foreground"></i>
                <input class="kt-switch kt-switch-sm" data-kt-theme-switch-state="dark" data-kt-theme-switch-toggle="true" type="checkbox" aria-label="{{ __('navigation.dark_mode') }}" />
            </div>
        </div>
    </div>
</div>
@endsection
