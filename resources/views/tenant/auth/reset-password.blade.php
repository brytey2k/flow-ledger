@extends('tenant.layouts.auth')

@section('title', 'Reset Password')

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
                    Reset Password
                </h3>
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

            <form action="{{ route('password.update') }}" method="POST" class="flex flex-col gap-5">
                @csrf

                <input type="hidden" name="token" value="{{ $token }}">

                <div class="flex flex-col gap-1">
                    <label class="kt-form-label font-normal text-mono" for="email">
                        Email
                    </label>
                    <input
                        class="kt-input"
                        id="email"
                        name="email"
                        placeholder="email@email.com"
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

                <div class="flex flex-col gap-1">
                    <label class="kt-form-label font-normal text-mono" for="password">
                        New Password
                    </label>
                    <div class="kt-input" data-kt-toggle-password="true" aria-invalid="@error('password') true @else false @enderror">
                        <input
                            id="password"
                            name="password"
                            placeholder="Enter new password"
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
                    @error('password')
                        <span class="text-xs text-destructive">{{ $message }}</span>
                    @enderror
                </div>

                <div class="flex flex-col gap-1">
                    <label class="kt-form-label font-normal text-mono" for="password_confirmation">
                        Confirm Password
                    </label>
                    <div class="kt-input" data-kt-toggle-password="true">
                        <input
                            id="password_confirmation"
                            name="password_confirmation"
                            placeholder="Confirm new password"
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
                    Reset Password
                </button>
            </form>

            <div class="text-center">
                <a class="text-sm kt-link" href="{{ route('login') }}">
                    Back to Sign In
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
