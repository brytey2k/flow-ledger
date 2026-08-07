<!-- User -->
@php
    $userName = trim((string) (auth()->user()?->name ?? 'User'));
    $userEmail = (string) (auth()->user()?->email ?? '');
    $avatarInitial = strtoupper(substr($userName !== '' ? $userName : 'U', 0, 1));
@endphp
<div class="shrink-0" data-kt-dropdown="true" data-kt-dropdown-offset="10px, 10px" data-kt-dropdown-offset-rtl="-20px, 10px"
    data-kt-dropdown-placement="bottom-end" data-kt-dropdown-placement-rtl="bottom-start" data-kt-dropdown-trigger="click">
    <div class="shrink-0 cursor-pointer" data-kt-dropdown-toggle="true">
        <div class="size-9 shrink-0 rounded-full bg-primary flex items-center justify-center text-sm font-semibold text-primary-foreground">
            {{ $avatarInitial }}
        </div>
    </div>
    <div class="kt-dropdown-menu w-[250px]" data-kt-dropdown-menu="true">
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
            <span class="kt-badge kt-badge-sm kt-badge-primary kt-badge-outline">
                Pro
            </span>
        </div>
        <ul class="kt-dropdown-menu-sub">
            <li>
                <div class="kt-dropdown-menu-separator">
                </div>
            </li>
        </ul>
        <div class="mb-2.5 flex flex-col gap-3.5 px-2.5 pt-1.5">
            <div class="flex items-center justify-between gap-2">
                <span class="flex items-center gap-2">
                    <i class="ki-filled ki-moon text-base text-muted-foreground">
                    </i>
                    <span class="text-2sm font-medium">
                        {{ __('navigation.dark_mode') }}
                    </span>
                </span>
                <input class="kt-switch" data-kt-theme-switch-state="dark" data-kt-theme-switch-toggle="true"
                    name="check" type="checkbox" value="1" />
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="kt-btn kt-btn-outline w-full justify-center">
                    {{ __('navigation.logout') }}
                </button>
            </form>
        </div>
    </div>
</div>
<!-- End of User -->
