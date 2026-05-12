<div class="kt-menu" data-kt-menu="true">
    <div class="kt-menu-item" data-kt-menu-item-offset="0, 10px" data-kt-menu-item-overflow="true"
         data-kt-menu-item-placement="bottom-end" data-kt-menu-item-toggle="dropdown"
         data-kt-menu-item-trigger="click">
        <div class="kt-menu-toggle btn btn-ghost flex items-center gap-2 cursor-pointer">
            <div class="flex flex-col items-end text-sm">
                <span class="font-medium text-foreground leading-none">{{ auth()->user()?->name }}</span>
                <span class="text-xs text-muted-foreground leading-none mt-1">{{ auth()->user()?->email }}</span>
            </div>
            <div class="flex size-9 items-center justify-center rounded-full bg-primary/10">
                <i class="ki-filled ki-user text-primary text-lg"></i>
            </div>
        </div>
        <div class="kt-menu-dropdown kt-menu-default w-[240px] py-2.5">
            <div class="px-3 py-2 flex items-center justify-between gap-2 border-b border-border mb-1">
                <span class="flex items-center gap-2">
                    <i class="ki-filled ki-moon text-base text-muted-foreground"></i>
                    <span class="text-2sm font-medium">{{ __('navigation.dark_mode') }}</span>
                </span>
                <input class="kt-switch" data-kt-theme-switch-state="dark" data-kt-theme-switch-toggle="true"
                    name="theme_mode" type="checkbox" value="1" />
            </div>
            <div class="px-3 py-2 flex items-center justify-between gap-2 border-b border-border mb-1">
                <span class="flex items-center gap-2">
                    <i class="ki-filled ki-translate text-base text-muted-foreground"></i>
                    <span class="text-2sm font-medium">{{ __('navigation.language') }}</span>
                </span>
                <form method="POST" action="{{ route('locale.update') }}" class="min-w-[110px]">
                    @csrf
                    <select
                        name="locale"
                        class="h-8 w-full rounded-md border border-border bg-background px-2 text-xs text-foreground"
                        onchange="this.form.submit()"
                    >
                        <option value="en" @selected(app()->getLocale() === 'en')>English</option>
                        <option value="fr" @selected(app()->getLocale() === 'fr')>Francais</option>
                    </select>
                </form>
            </div>
            <div class="kt-menu-item">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="kt-menu-link w-full text-left">
                        <span class="kt-menu-icon">
                            <i class="ki-filled ki-exit-right"></i>
                        </span>
                        <span class="kt-menu-title">{{ __('navigation.logout') }}</span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
