@extends('landlord.layouts.auth')

@section('title', __('auth.registration_halted_heading'))

@section('content')
<div class="mb-7 flex items-center justify-end gap-2">
    <form action="{{ route('landlord.locale.update') }}" method="POST">
        @csrf
        <label class="sr-only" for="register-halted-locale">{{ __('navigation.language') }}</label>
        <select
            class="h-8 rounded-md border border-border bg-background px-2 text-xs text-foreground"
            id="register-halted-locale"
            name="locale"
            onchange="this.form.submit()"
        >
            @foreach (config('locales.supported') as $code => $meta)
                <option value="{{ $code }}" @selected(app()->getLocale() === $code)>{{ $meta['native_label'] }}</option>
            @endforeach
        </select>
    </form>
    <div class="flex items-center gap-2" x-data="themeToggle">
        <button type="button" role="switch" :aria-checked="dark" @click="toggleTheme()"
                class="sgh-switch" :class="dark ? 'bg-primary' : 'bg-muted'"
                aria-label="{{ __('navigation.dark_mode') }}">
            <span class="sgh-switch-thumb" :class="dark ? 'translate-x-[18px]' : 'translate-x-0.5'"></span>
        </button>
    </div>
</div>

<div class="flex flex-col items-start gap-5 text-start">
    <div class="flex size-14 items-center justify-center rounded-full bg-primary/10">
        <x-tabler-info-circle-filled class="size-6 text-primary" />
    </div>

    <div class="flex flex-col gap-2">
        <h1 class="text-[27px] font-semibold tracking-tight text-foreground">{{ __('auth.registration_halted_heading') }}</h1>
        <p class="text-[14.5px] leading-relaxed text-muted-foreground">{{ __('auth.registration_halted_subtitle') }}</p>
    </div>

    <div class="w-full rounded-lg border border-border bg-muted/40 p-5">
        <p class="text-sm text-foreground">{{ __('auth.registration_halted_body') }}</p>
        <a class="sgh-btn sgh-btn-primary mt-4 flex justify-center gap-2" href="{{ config('app.org_url') }}" rel="noopener noreferrer" target="_blank">
            <x-tabler-external-link class="size-4" />
            {{ __('auth.registration_halted_cta') }}
        </a>
    </div>

    <a class="text-sm sgh-link" href="{{ route('landlord.home') }}">
        {{ __('auth.back_to_home') }}
    </a>
</div>
@endsection
