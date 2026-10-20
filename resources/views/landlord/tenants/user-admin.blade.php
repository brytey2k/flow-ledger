@extends('landlord.layouts.base')

@section('content')
@include('landlord.tenants.partials.admin-nav')
<div class="sgh-container-fixed">
    <div class="flex flex-col gap-2 pb-7.5">
        <h1 class="text-xl font-medium leading-none text-mono">User Admin</h1>
        <p class="text-sm text-secondary-foreground">Sign users out, re-send invitations, reactivate suspended accounts, and check SSO linkage.</p>
    </div>
</div>

<div class="sgh-container-fixed">
    <div class="grid gap-5">
        <form method="GET" class="flex gap-2">
            <input name="q" value="{{ $search }}" class="sgh-input w-72" placeholder="Search name or email…">
            <button class="sgh-btn sgh-btn-light" type="submit">Search</button>
        </form>

        <div class="sgh-card">
            <div class="sgh-card-content p-0">
                @if(count($users) === 0)
                    <div class="p-8 text-center text-muted-foreground">No users found.</div>
                @else
                    <div class="divide-y">
                        @foreach($users as $user)
                            <div class="flex flex-wrap items-center justify-between gap-4 p-4 text-sm">
                                <div class="flex flex-col gap-1">
                                    <span class="font-medium">{{ $user['name'] }}</span>
                                    <span class="text-2sm text-secondary-foreground">{{ $user['email'] }}</span>
                                    <div class="flex gap-1.5">
                                        <span class="sgh-badge {{ $user['status'] === 'active' ? 'sgh-badge-success' : 'sgh-badge-warning' }}">{{ \Illuminate\Support\Str::headline($user['status']) }}</span>
                                        <span class="sgh-badge sgh-badge-outline">
                                            {{ $user['is_oidc_user'] ? ($user['sso_linked'] ? 'SSO linked' : 'SSO not linked yet') : 'Local login' }}
                                        </span>
                                    </div>
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    <form method="POST" action="{{ route('landlord.tenants.user-admin.force-logout', [$tenant, $user['id']]) }}">@csrf<button class="sgh-btn sgh-btn-sm sgh-btn-light" type="submit">Force logout</button></form>
                                    @if($user['status'] === 'invited')
                                        <form method="POST" action="{{ route('landlord.tenants.user-admin.resend-invite', [$tenant, $user['id']]) }}">@csrf<button class="sgh-btn sgh-btn-sm sgh-btn-light" type="submit">Resend invite</button></form>
                                    @endif
                                    @if($user['status'] === 'suspended')
                                        <form method="POST" action="{{ route('landlord.tenants.user-admin.unlock', [$tenant, $user['id']]) }}">@csrf<button class="sgh-btn sgh-btn-sm sgh-btn-primary" type="submit">Unlock</button></form>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
