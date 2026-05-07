@extends('landlord.layouts.app')

@section('title', 'Create Tenant')

@section('content')
<div class="kt-container-fixed">
    <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono">Create Tenant</h1>
            <div class="flex items-center gap-2 text-sm font-normal text-secondary-foreground">
                Provide details to create a new tenant and bootstrap its database
            </div>
        </div>
        <div class="flex items-center gap-2.5">
            <a class="kt-btn kt-btn-outline" href="{{ route('landlord.tenants.index') }}">
                <i class="ki-filled ki-arrow-left"></i>
                Back to Tenants
            </a>
        </div>
    </div>
</div>

<div class="kt-container-fixed">
    @if ($errors->any())
        <div class="mb-5 rounded-lg border border-destructive/30 bg-destructive/10 p-4 text-destructive">
            <div class="font-medium mb-2">Please fix the following errors:</div>
            <ul class="list-disc ps-5 text-sm">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid gap-5 lg:gap-7.5">
        <div class="kt-card">
            <div class="kt-card-header">
                <h3 class="kt-card-title">Tenant Details</h3>
            </div>
            <div class="kt-card-content">
                <form method="POST" action="{{ route('landlord.tenants.store') }}" class="grid gap-7">
                    @csrf

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                        {{-- Tenant ID --}}
                        <div>
                            <label class="kt-form-label block mb-2" for="id">
                                Subdomain <span class="text-destructive">*</span>
                            </label>
                            <input id="id" name="id" type="text" value="{{ old('id') }}"
                                   class="kt-input w-full" placeholder="e.g. acme"
                                   required aria-invalid="@error('id') true @else false @enderror" />
                            <div class="mt-1 text-xs text-muted-foreground">
                                Lowercase letters, numbers, dashes and underscores only.
                            </div>
                            @error('id')
                                <p class="mt-1 text-sm text-destructive">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Tenant Name --}}
                        <div>
                            <label class="kt-form-label block mb-2" for="name">
                                Organisation Name <span class="text-destructive">*</span>
                            </label>
                            <input id="name" name="name" type="text" value="{{ old('name') }}"
                                   class="kt-input w-full" placeholder="e.g. Acme Corp"
                                   required aria-invalid="@error('name') true @else false @enderror" />
                            @error('name')
                                <p class="mt-1 text-sm text-destructive">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Admin Email --}}
                        <div>
                            <label class="kt-form-label block mb-2" for="admin_email">
                                Admin Email <span class="text-destructive">*</span>
                            </label>
                            <input id="admin_email" name="admin_email" type="email" value="{{ old('admin_email') }}"
                                   class="kt-input w-full" placeholder="admin@example.com"
                                   required aria-invalid="@error('admin_email') true @else false @enderror" />
                            @error('admin_email')
                                <p class="mt-1 text-sm text-destructive">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Admin Password --}}
                        <div>
                            <label class="kt-form-label block mb-2" for="admin_password">
                                Admin Password <span class="text-destructive">*</span>
                            </label>
                            <input id="admin_password" name="admin_password" type="password"
                                   class="kt-input w-full" placeholder="Minimum 8 characters"
                                   required aria-invalid="@error('admin_password') true @else false @enderror" />
                            <div class="mt-1 text-xs text-muted-foreground">
                                Use a mix of letters, numbers, and symbols.
                            </div>
                            @error('admin_password')
                                <p class="mt-1 text-sm text-destructive">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="pt-5 mt-2 flex justify-start items-center gap-2.5">
                        <button type="submit" class="kt-btn kt-btn-primary">
                            <i class="ki-filled ki-plus"></i>
                            Create Tenant
                        </button>
                        <a class="kt-btn kt-btn-light" href="{{ route('landlord.tenants.index') }}">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
