@extends('tenant.layouts.auth')

@section('title', 'Forgot Password')

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
                    Forgot Password
                </h3>
                <p class="text-sm text-secondary-foreground">
                    Enter your email and we'll send you a reset link.
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
                        Email
                    </label>
                    <input
                        class="kt-input"
                        id="email"
                        name="email"
                        placeholder="email@email.com"
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
                    Send Reset Link
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
