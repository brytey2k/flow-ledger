@extends('tenant.layouts.auth')

@section('title', __('auth.change_password'))

@section('content')
<h1 class="mb-2 text-[27px] font-semibold tracking-tight text-foreground">{{ __('auth.change_password') }}</h1>
<p class="mb-8 text-[14.5px] leading-relaxed text-muted-foreground">{{ __('auth.change_password_hint') }}</p>

@if ($errors->any())
    <div class="sgh-alert sgh-alert-danger mb-5">
        <x-tabler-info-circle-filled class="size-4" />
        <ul class="list-disc list-inside">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form action="{{ route('password.change.update') }}" method="POST" class="flex flex-col gap-5">
    @csrf
    @method('PUT')

    <div class="flex flex-col gap-1" x-data="{ show: false }">
        <label class="sgh-label" for="password">
            {{ __('auth.new_password') }}
        </label>
        <div class="sgh-input flex items-center gap-1 py-0" aria-invalid="@error('password') true @else false @enderror">
            <input
                class="grow border-0 bg-transparent px-0 py-2 focus:outline-none focus:ring-0"
                id="password"
                name="password"
                placeholder="{{ __('auth.enter_new_password') }}"
                :type="show ? 'text' : 'password'"
                required
                autofocus
            />
            <button class="sgh-btn sgh-btn-ghost sgh-btn-icon sgh-btn-sm -me-1.5" @click="show = !show" type="button" aria-label="{{ __('auth.new_password') }}">
                <x-tabler-eye-filled class="text-muted-foreground" x-show="!show" />
                <x-tabler-eye-closed class="text-muted-foreground" x-show="show" x-cloak />
            </button>
        </div>
        @error('password')
            <span class="text-xs text-destructive">{{ $message }}</span>
        @enderror
    </div>

    <div class="flex flex-col gap-1" x-data="{ show: false }">
        <label class="sgh-label" for="password_confirmation">
            {{ __('auth.confirm_password') }}
        </label>
        <div class="sgh-input flex items-center gap-1 py-0">
            <input
                class="grow border-0 bg-transparent px-0 py-2 focus:outline-none focus:ring-0"
                id="password_confirmation"
                name="password_confirmation"
                placeholder="{{ __('auth.confirm_new_password') }}"
                :type="show ? 'text' : 'password'"
                required
            />
            <button class="sgh-btn sgh-btn-ghost sgh-btn-icon sgh-btn-sm -me-1.5" @click="show = !show" type="button" aria-label="{{ __('auth.confirm_password') }}">
                <x-tabler-eye-filled class="text-muted-foreground" x-show="!show" />
                <x-tabler-eye-closed class="text-muted-foreground" x-show="show" x-cloak />
            </button>
        </div>
    </div>

    <button class="sgh-btn sgh-btn-primary flex justify-center grow" type="submit">
        {{ __('auth.change_password') }}
    </button>
</form>

<div class="mt-6">
    <form action="{{ route('logout') }}" method="POST">
        @csrf
        <button type="submit" class="text-sm text-muted-foreground hover:text-foreground underline">
            {{ __('auth.sign_out_later') }}
        </button>
    </form>
</div>
@endsection
