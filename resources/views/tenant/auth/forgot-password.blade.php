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
    <div class="kt-card max-w-[370px] w-full">
        <div class="kt-card-content flex flex-col gap-5 p-10">
            <div class="text-center mb-2.5">
                <h3 class="text-lg font-medium text-mono leading-none mb-2.5">
                    {{ __('auth.forgot_password') }}
                </h3>
                <p class="text-sm text-secondary-foreground">
                    {{ __('auth.forgot_password_hint') }}
                </p>
            </div>

            @if (session('status'))
                <div class="kt-alert kt-alert-light kt-alert-success">
                    <span class="kt-alert-icon"><i class="ki-filled ki-check-circle text-xl"></i></span>
                    <div class="kt-alert-content">
                        <p class="kt-alert-description">{{ session('status') }}</p>
                    </div>
                </div>
            @endif

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

            <form action="{{ route('password.email') }}" method="POST" class="flex flex-col gap-5">
                @csrf

                <div class="flex flex-col gap-1">
                    <label class="kt-form-label font-normal text-mono" for="email">
                        {{ __('auth.email') }}
                    </label>
                    <input
                        class="kt-input"
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

                <button class="kt-btn kt-btn-primary flex justify-center grow" type="submit">
                    {{ __('auth.send_reset_link') }}
                </button>
            </form>

            <div class="text-center">
                <a class="text-sm kt-link" href="{{ route('login') }}">
                    {{ __('auth.back_to_sign_in') }}
                </a>
            </div>
        </div>
        <div class="border-t border-border px-10 py-4">
            <form method="POST" action="{{ route('locale.update') }}" class="flex justify-center">
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
        </div>
    </div>
</div>
@endsection
