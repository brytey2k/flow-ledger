@extends('landlord.layouts.app')

@section('title', 'Change Password')

@section('content')
@php
    $landlordUser = auth('landlord')->user();
    $fullName = trim((string) ($landlordUser?->name ?? 'Landlord Admin'));
    $avatarInitial = strtoupper(substr($fullName !== '' ? $fullName : 'L', 0, 1));
@endphp

<div class="fl-container-fixed">
    <div class="flex flex-wrap items-center justify-between gap-5 pb-7.5 lg:items-end">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono">Change Password</h1>
            <div class="flex items-center gap-2 text-sm font-normal text-secondary-foreground">
                Keep your account secure by using a strong, unique password
            </div>
        </div>
    </div>
</div>

<div class="fl-container-fixed">
    <div class="grid grid-cols-1 gap-5 lg:grid-cols-3 lg:gap-7.5">
        <div class="lg:col-span-1 flex flex-col gap-5">
            <div class="fl-card">
                <div class="fl-card-content pt-6 pb-5">
                    <div class="flex flex-col items-center gap-3 text-center">
                        <div class="size-16 rounded-full bg-primary flex items-center justify-center text-2xl font-bold text-primary-foreground shrink-0">
                            {{ $avatarInitial }}
                        </div>
                        <div class="flex flex-col gap-1">
                            <span class="text-base font-semibold text-foreground">{{ $fullName }}</span>
                            <span class="text-sm text-secondary-foreground">{{ (string) ($landlordUser?->email ?? '') }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="fl-card">
                <div class="fl-card-header">
                    <h3 class="fl-card-title flex items-center gap-2">
                        <x-tabler-shield-check-filled class="text-primary" />
                        Password Tips
                    </h3>
                </div>
                <div class="fl-card-content">
                    <ul class="flex flex-col gap-3">
                        <li class="flex items-start gap-2.5 text-sm text-secondary-foreground">
                            <x-tabler-circle-check-filled class="text-success mt-0.5 shrink-0" />
                            At least 8 characters long
                        </li>
                        <li class="flex items-start gap-2.5 text-sm text-secondary-foreground">
                            <x-tabler-circle-check-filled class="text-success mt-0.5 shrink-0" />
                            Mix of uppercase and lowercase letters
                        </li>
                        <li class="flex items-start gap-2.5 text-sm text-secondary-foreground">
                            <x-tabler-circle-check-filled class="text-success mt-0.5 shrink-0" />
                            Include numbers and special characters
                        </li>
                        <li class="flex items-start gap-2.5 text-sm text-secondary-foreground">
                            <x-tabler-circle-check-filled class="text-success mt-0.5 shrink-0" />
                            Avoid reusing previous passwords
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="lg:col-span-2">
            <div class="fl-card">
                <div class="fl-card-header">
                    <h3 class="fl-card-title flex items-center gap-2">
                        <x-tabler-lock-filled class="text-primary" />
                        Update Password
                    </h3>
                </div>
                <div class="fl-card-content">
                    <form action="{{ route('landlord.password.update') }}" class="grid gap-6" method="POST">
                        @csrf
                        @method('PUT')

                        <div>
                            <label class="fl-form-label block mb-2" for="current_password">
                                Current Password
                                <span class="text-destructive">*</span>
                            </label>
                            <input
                                class="fl-input w-full"
                                id="current_password"
                                name="current_password"
                                type="password"
                                required
                                autocomplete="current-password"
                                aria-invalid="@error('current_password') true @else false @enderror"
                            />
                            <p class="mt-1 text-xs text-muted-foreground">Enter the password you currently use to sign in.</p>
                            @error('current_password')
                                <p class="mt-1 text-sm text-destructive">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="border-t border-border"></div>

                        <div>
                            <label class="fl-form-label block mb-2" for="password">
                                New Password
                                <span class="text-destructive">*</span>
                            </label>
                            <input
                                class="fl-input w-full"
                                id="password"
                                name="password"
                                type="password"
                                required
                                autocomplete="new-password"
                                aria-invalid="@error('password') true @else false @enderror"
                            />
                            <p class="mt-1 text-xs text-muted-foreground">Must be at least 8 characters.</p>
                            @error('password')
                                <p class="mt-1 text-sm text-destructive">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="fl-form-label block mb-2" for="password_confirmation">
                                Confirm New Password
                                <span class="text-destructive">*</span>
                            </label>
                            <input
                                class="fl-input w-full"
                                id="password_confirmation"
                                name="password_confirmation"
                                type="password"
                                required
                                autocomplete="new-password"
                            />
                            <p class="mt-1 text-xs text-muted-foreground">Re-enter your new password to confirm.</p>
                        </div>

                        <div class="pt-2 flex items-center gap-2.5">
                            <button class="fl-btn fl-btn-primary" type="submit">
                                <x-tabler-lock-filled />
                                Update Password
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
