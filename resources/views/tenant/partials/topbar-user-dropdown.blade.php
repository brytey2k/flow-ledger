<!-- User -->
@php
    $userName = trim((string) (auth()->user()?->name ?? 'User'));
    $userEmail = (string) (auth()->user()?->email ?? '');
    $avatarInitial = strtoupper(substr($userName !== '' ? $userName : 'U', 0, 1));
@endphp
<div class="relative shrink-0" x-data="dropdown">
    <div class="shrink-0 cursor-pointer" @click="toggle">
        <div class="size-9 shrink-0 rounded-full bg-primary flex items-center justify-center text-sm font-semibold text-primary-foreground">
            {{ $avatarInitial }}
        </div>
    </div>
    <div x-show="open" x-cloak @click.outside="close()" class="sgh-dropdown-panel w-[250px]">
        <div class="flex items-center justify-between gap-1.5 px-2.5 py-1.5">
            <div class="flex items-center gap-2">
                <div class="size-9 shrink-0 rounded-full bg-primary flex items-center justify-center text-sm font-semibold text-primary-foreground">
                    {{ $avatarInitial }}
                </div>
                <div class="flex flex-col gap-1.5">
                    <span class="text-sm font-semibold leading-none text-foreground">
                        {{ $userName }}
                    </span>
                    <span class="text-xs font-medium leading-none text-secondary-foreground">
                        {{ $userEmail }}
                    </span>
                </div>
            </div>
            <span class="sgh-badge sgh-badge-sm sgh-badge-primary sgh-badge-outline">
                Pro
            </span>
        </div>
        <div class="sgh-dropdown-separator"></div>
        <div class="mb-2.5 flex flex-col gap-3.5 px-2.5 pt-1.5" x-data="themeToggle">
            <div class="flex items-center justify-between gap-2">
                <span class="flex items-center gap-2">
                    <x-tabler-moon-filled class="text-base text-muted-foreground" />
                    <span class="text-2sm font-medium">
                        {{ __('navigation.dark_mode') }}
                    </span>
                </span>
                <button type="button" role="switch" :aria-checked="dark" @click="toggleTheme()"
                    class="sgh-switch" :class="dark ? 'bg-primary' : 'bg-muted'"
                    aria-label="{{ __('navigation.dark_mode') }}">
                    <span class="sgh-switch-thumb" :class="dark ? 'translate-x-[18px]' : 'translate-x-0.5'"></span>
                </button>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="sgh-btn sgh-btn-outline w-full justify-center">
                    {{ __('navigation.logout') }}
                </button>
            </form>
        </div>
    </div>
</div>
<!-- End of User -->
