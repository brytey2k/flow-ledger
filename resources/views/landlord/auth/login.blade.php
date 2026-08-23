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
    <div class="sgh-card max-w-[370px] w-full">
        <div class="flex justify-center" style="padding-top: 3.5rem; padding-bottom: 1.25rem;">
            <img class="dark:hidden h-6 w-auto" src="{{ asset('assets/media/app/flowledger_logo_light.png') }}" alt="{{ config('app.name') }}" />
            <img class="hidden dark:block h-6 w-auto" src="{{ asset('assets/media/app/flowledger_logo_dark.png') }}" alt="{{ config('app.name') }}" />
        </div>
        <form action="{{ route('landlord.do-login') }}" class="sgh-card-content flex flex-col gap-5 p-10" id="sign_in_form" method="POST">
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
                <label class="sgh-form-label font-normal text-mono" for="email">
                    Email
                </label>
                <input
                    class="sgh-input @error('email') border-red-500 @enderror"
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
                    <label class="sgh-form-label font-normal text-mono" for="password">
                        Password
                    </label>
                    <a class="text-sm sgh-link shrink-0" href="#">
                        Forgot Password?
                    </a>
                </div>
                <div class="sgh-input flex items-center gap-1 py-0 @error('password') border-red-500 @enderror" x-data="{ show: false }">
                    <input
                        class="grow border-0 bg-transparent px-0 py-2 focus:outline-none focus:ring-0"
                        id="password"
                        name="password"
                        placeholder="Enter Password"
                        :type="show ? 'text' : 'password'"
                        required
                    />
                    <button class="sgh-btn sgh-btn-sm sgh-btn-ghost sgh-btn-icon bg-transparent! -me-1.5" @click="show = !show" type="button">
                        <x-tabler-eye-filled class="text-muted-foreground" x-show="!show" />
                        <x-tabler-eye-closed class="text-muted-foreground" x-show="show" x-cloak />
                    </button>
                </div>
                @error('password')
                    <span class="text-xs text-red-500">{{ $message }}</span>
                @enderror
            </div>

            <label class="sgh-label">
                <input class="sgh-checkbox sgh-checkbox-sm" name="remember" type="checkbox" value="1" {{ old('remember') ? 'checked' : '' }}/>
                <span class="sgh-checkbox-label">
                    Remember me
                </span>
            </label>

            <button class="sgh-btn sgh-btn-primary flex justify-center grow" type="submit">
                Sign In
            </button>
        </form>
        <div class="border-t border-border px-10 py-4">
            <form method="POST" action="{{ route('locale.update') }}" class="flex justify-center">
                @csrf
                <label class="sr-only" for="landlord-auth-locale">{{ __('navigation.language') }}</label>
                <select
                    id="landlord-auth-locale"
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
