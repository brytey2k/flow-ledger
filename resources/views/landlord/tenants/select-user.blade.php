@extends('landlord.layouts.app')

@section('title', 'Impersonate User — ' . ($tenant->name ?? $tenant->id))

@section('content')
<div class="kt-container-fixed">
    <div class="flex flex-wrap items-center justify-between gap-5 pb-7.5 lg:items-end">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono">
                Impersonate User
            </h1>
            <div class="flex items-center gap-2 text-sm font-normal text-secondary-foreground">
                Tenant: <span class="font-medium text-foreground">{{ $tenant->name ?? $tenant->id }}</span>
            </div>
        </div>
        <div class="flex items-center gap-2.5">
            <a class="kt-btn kt-btn-ghost" href="{{ route('landlord.tenants.index') }}">
                <i class="ki-filled ki-arrow-left"></i>
                Back to Tenants
            </a>
        </div>
    </div>
</div>

<div class="kt-container-fixed">
    <div class="rounded-lg border border-warning/30 bg-warning/10 p-4 text-sm text-warning flex items-center gap-2">
        <i class="ki-filled ki-information-2"></i>
        You are about to impersonate a user in <span class="font-semibold">{{ $tenant->name ?? $tenant->id }}</span>. This will open a new browser tab logged in as that user.
    </div>

    <div class="kt-card kt-card-grid">
        <div class="kt-card-header">
            <h3 class="kt-card-title">Select a User</h3>
            <div class="flex items-center gap-2">
                <span class="badge badge-sm badge-outline">
                    {{ $users->total() }} {{ Str::plural('User', $users->total()) }}
                </span>
            </div>
        </div>

        @if($users->isEmpty())
            <div class="kt-card-content flex flex-col gap-4 p-5 lg:p-7.5 lg:pt-4">
                <div class="flex flex-col items-center justify-center py-12">
                    <i class="ki-filled ki-people text-6xl text-muted-foreground mb-4"></i>
                    <h3 class="text-lg font-medium text-foreground mb-2">No users found</h3>
                    <p class="text-sm text-secondary-foreground">This tenant has no users yet.</p>
                </div>
            </div>
        @else
            <div class="kt-card-table">
                <div class="kt-scrollable-x-auto border-b border-border">
                    <table class="kt-table kt-table-border table-fixed">
                        <thead>
                            <tr>
                                <th class="min-w-[200px]">
                                    <span class="kt-table-col">
                                        <span class="kt-table-col-label">Name</span>
                                    </span>
                                </th>
                                <th class="min-w-[200px]">
                                    <span class="kt-table-col">
                                        <span class="kt-table-col-label">Email</span>
                                    </span>
                                </th>
                                <th class="min-w-[150px]">
                                    <span class="kt-table-col">
                                        <span class="kt-table-col-label">Branch</span>
                                    </span>
                                </th>
                                <th class="min-w-[120px] text-center">
                                    <span class="kt-table-col">
                                        <span class="kt-table-col-label">Action</span>
                                    </span>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($users as $user)
                                <tr>
                                    <td>
                                        <span class="text-sm font-medium text-mono">
                                            {{ trim($user->first_name . ' ' . $user->last_name) }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="text-sm text-secondary-foreground">{{ $user->email }}</span>
                                    </td>
                                    <td>
                                        <span class="text-sm text-secondary-foreground">
                                            {{ $user->branch?->name ?? '—' }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <form method="POST" action="{{ route('landlord.impersonate', $tenant) }}" target="_blank">
                                            @csrf
                                            <input type="hidden" name="user_identifier" value="{{ $user->id }}">
                                            <button type="submit" class="kt-btn kt-btn-sm kt-btn-primary">
                                                Impersonate
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            @if($users->hasPages())
                <div class="kt-card-footer flex justify-end p-4">
                    {{ $users->links() }}
                </div>
            @endif
        @endif
    </div>
</div>
@endsection
