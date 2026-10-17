@extends('tenant.layouts.auth')

@section('title', __('auth.sso_confirm_title'))

@section('content')
<h1 class="mb-2 text-[27px] font-semibold tracking-tight text-foreground">{{ __('auth.sso_confirm_title') }}</h1>
<p class="mb-6 text-[14.5px] leading-relaxed text-muted-foreground">{{ __('auth.sso_confirm_body') }}</p>

<div class="mb-6 rounded-md border border-border p-4">
    <div class="text-sm font-medium text-foreground">{{ $name }}</div>
    <div class="text-sm text-muted-foreground">{{ $email }}</div>
</div>

<p class="mb-6 text-sm text-muted-foreground">{{ __('auth.sso_confirm_warning') }}</p>

<form method="POST" action="{{ route('sso.finalize.confirm') }}" class="flex flex-col gap-3">
    @csrf
    <input type="hidden" name="token" value="{{ $token }}">
    <button type="submit" class="sgh-btn sgh-btn-primary flex justify-center">{{ __('auth.sso_confirm_continue') }}</button>
    <a class="sgh-btn sgh-btn-outline flex justify-center" href="{{ route('login') }}">{{ __('auth.sso_confirm_cancel') }}</a>
</form>
@endsection
