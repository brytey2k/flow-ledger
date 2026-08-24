<!-- User -->
@php
    $userName = trim((string) session('user_name', 'User'));
    $avatarInitial = strtoupper(substr($userName !== '' ? $userName : 'U', 0, 1));
@endphp
<div class="relative shrink-0" x-data="dropdown">
    <button type="button" class="sgh-btn sgh-btn-outline sgh-btn-sm shrink-0 !rounded-full gap-2 py-1 pe-2.5 ps-1" @click="toggle" :aria-expanded="open">
        <div class="size-7 shrink-0 rounded-full bg-primary flex items-center justify-center text-xs font-semibold text-primary-foreground">
            {{ $avatarInitial }}
        </div>
        <x-tabler-chevron-down-filled class="size-2.5 text-muted-foreground" />
    </button>
    <div x-show="open" x-cloak @click.outside="close()" class="sgh-dropdown-panel w-[250px]">
        <div class="flex items-center justify-between gap-1.5 px-2.5 py-1.5">
            <div class="flex items-center gap-2">
                <div class="size-9 shrink-0 rounded-full bg-primary flex items-center justify-center text-sm font-semibold text-primary-foreground">
                    {{ $avatarInitial }}
                </div>
                <div class="flex flex-col gap-1.5">
                    <span class="text-sm font-semibold leading-none text-foreground">
                        {{ session('user_name', 'User') }}
                    </span>
                    <span class="text-xs font-medium leading-none text-secondary-foreground">
                        {{ session('user_email', '') }}
                    </span>
                </div>
            </div>
            <span class="sgh-badge sgh-badge-sm sgh-badge-primary sgh-badge-outline">
                Pro
            </span>
        </div>
        <div class="sgh-dropdown-separator"></div>
        <div class="py-1">
            <a class="sgh-dropdown-item" href="{{ "#" }}">
                <x-tabler-lock-filled />
                Change Password
            </a>
        </div>
        <div class="sgh-dropdown-separator"></div>
        <div class="mb-2.5 flex flex-col gap-3.5 px-2.5 pt-1.5" x-data="themeToggle">
            <div class="flex items-center justify-between gap-2">
                <span class="flex items-center gap-2">
                    <x-tabler-moon-filled class="size-4 text-muted-foreground" />
                    <span class="text-2sm font-medium">
                        Dark Mode
                    </span>
                </span>
                <button type="button" role="switch" :aria-checked="dark" @click="toggleTheme()"
                    class="sgh-switch" :class="dark ? 'bg-primary' : 'bg-muted'"
                    aria-label="Dark Mode">
                    <span class="sgh-switch-thumb" :class="dark ? 'translate-x-[18px]' : 'translate-x-0.5'"></span>
                </button>
            </div>
            <form method="POST" action="{{ route('landlord.logout') }}">
                @csrf
                <button type="submit" class="sgh-btn sgh-btn-outline w-full justify-center">
                    Log out
                </button>
            </form>
        </div>
    </div>
</div>
<!-- End of User -->
