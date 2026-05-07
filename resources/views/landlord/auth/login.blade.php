@extends('landlord.layouts.auth')

@section('title', 'Sign In')

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
        <form action="{{ route('landlord.do-login') }}" class="kt-card-content flex flex-col gap-5 p-10" id="sign_in_form" method="POST">
            @csrf

            @if ($errors->any())
            <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-800 dark:text-red-200 px-4 py-3 rounded">
                <ul class="list-disc list-inside text-sm">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            <div class="flex flex-col gap-1">
                <label class="kt-form-label font-normal text-mono" for="email">
                    Email
                </label>
                <input
                    class="kt-input @error('email') border-red-500 @enderror"
                    id="email"
                    name="email"
                    placeholder="email@email.com"
                    type="email"
                    value="{{ old('email') }}"
                    required
                    autofocus
                />
                @error('email')
                    <span class="text-xs text-red-500">{{ $message }}</span>
                @enderror
            </div>

            <div class="flex flex-col gap-1">
                <div class="flex items-center justify-between gap-1">
                    <label class="kt-form-label font-normal text-mono" for="password">
                        Password
                    </label>
                    <a class="text-sm kt-link shrink-0" href="#">
                        Forgot Password?
                    </a>
                </div>
                <div class="kt-input @error('password') border-red-500 @enderror" data-kt-toggle-password="true">
                    <input
                        id="password"
                        name="password"
                        placeholder="Enter Password"
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
                    <span class="text-xs text-red-500">{{ $message }}</span>
                @enderror
            </div>

            <label class="kt-label">
                <input class="kt-checkbox kt-checkbox-sm" name="remember" type="checkbox" value="1" {{ old('remember') ? 'checked' : '' }}/>
                <span class="kt-checkbox-label">
                    Remember me
                </span>
            </label>

            <button class="kt-btn kt-btn-primary flex justify-center grow" type="submit">
                Sign In
            </button>
        </form>
    </div>
</div>
@endsection
