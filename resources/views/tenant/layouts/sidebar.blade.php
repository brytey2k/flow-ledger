@php
    use App\Enums\Tenant\PermissionKey;

    $tenantName = tenant()->name ?? config('app.name');
    $tenantInitials = collect(preg_split('/\s+/', trim($tenantName)))
        ->filter()
        ->map(fn (string $word) => mb_strtoupper(mb_substr($word, 0, 1)))
        ->take(2)
        ->implode('');
@endphp
<!-- Sidebar -->
<div x-show="$store.sidebarDrawer.open" x-cloak class="fixed inset-0 z-10 bg-foreground/40 lg:hidden" @click="$store.sidebarDrawer.hide()"></div>
<div
    class="sgh-sidebar fixed bottom-0 top-0 z-20 hidden shrink-0 flex-col items-stretch border-e border-e-border bg-background pt-3 lg:flex"
    :class="{ 'flex': $store.sidebarDrawer.open, 'is-hover-expanded': collapsed && hovering }"
    @mouseenter="hovering = true" @mouseleave="hovering = false" id="sidebar">
    <div class="relative hidden shrink-0 items-center justify-between px-3 lg:flex lg:px-6"
         id="sidebar_header">
        @php
            $service = app(\App\Services\SettingsService::class);
            $lightLogoUrl = $service->getLightLogoUrl();
            $darkLogoUrl = $service->getDarkLogoUrl();
            $smallLogoUrl = $service->getSmallLogoUrl();
        @endphp
        <a class="dark:hidden" href="{{ route('dashboard') }}">
            @if($lightLogoUrl)
                <img class="default-logo" src="{{ $lightLogoUrl }}" alt="Logo"/>
            @else
                <img class="default-logo" src="{{ asset('assets/media/app/flowledger_logo_light.png') }}"/>
            @endif
            @if($smallLogoUrl)
                <img class="small-logo" src="{{ $smallLogoUrl }}" alt="Logo"/>
            @else
                <img class="small-logo" src="{{ asset('assets/media/app/flowledger_icon_light.png') }}"/>
            @endif
        </a>
        <a class="hidden dark:block" href="{{ route('dashboard') }}">
            @if($darkLogoUrl)
                <img class="default-logo" src="{{ $darkLogoUrl }}" alt="Logo"/>
            @else
                <img class="default-logo" src="{{ asset('assets/media/app/flowledger_logo_dark.png') }}"/>
            @endif
            @if($smallLogoUrl)
                <img class="small-logo" src="{{ $smallLogoUrl }}" alt="Logo"/>
            @else
                <img class="small-logo" src="{{ asset('assets/media/app/flowledger_icon_dark.png') }}"/>
            @endif
        </a>
        <button
            @click="toggle" :aria-expanded="!collapsed"
            class="sgh-btn sgh-btn-outline sgh-btn-icon absolute start-full top-2/4 size-[30px] -translate-x-2/4 -translate-y-2/4 rtl:translate-x-2/4 hidden lg:inline-flex"
            id="sidebar_toggle">
            <span :class="{ 'rotate-180 rtl:rotate-0': collapsed }" class="inline-flex transition-transform duration-300">
                <x-tabler-chevron-left class="rtl:rotate-180" />
            </span>
        </button>
    </div>
    <div class="hidden shrink-0 px-3 pt-4 lg:block lg:px-6">
        <div class="sgh-sidebar-tenant-card">
            <span class="sgh-sidebar-tenant-avatar">{{ $tenantInitials ?: '?' }}</span>
            <span class="min-w-0 flex-1">
                <span class="sgh-sidebar-tenant-name">{{ $tenantName }}</span>
                <span class="sgh-sidebar-tenant-kind">{{ __('navigation.tenant_kind') }}</span>
            </span>
        </div>
    </div>
    <div class="flex min-h-0 grow py-5 pe-2" id="sidebar_content">
        <div class="flex min-h-0 grow overflow-y-auto pe-1 ps-2 lg:pe-3 lg:ps-5" id="sidebar_scrollable">
            <div class="flex grow flex-col gap-1" id="sidebar_menu">

                {{-- Dashboard --}}
                <a href="{{ route('dashboard') }}" class="sgh-nav-item gap-2.5 {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                        <span class="sgh-nav-icon">
                            <x-tabler-layout-grid-filled class="size-4" />
                        </span>
                        <span class="sgh-nav-title text-nowrap">{{ __('navigation.dashboard') }}</span>
                    </a>

                {{-- Requests --}}
                @canany([PermissionKey::AccessPaymentRequests->value, PermissionKey::AccessRetirementRequests->value])
                    <div class="sgh-nav-heading">
                        {{ __('navigation.sections.requests') }}
                    </div>

                    @can(PermissionKey::AccessPaymentRequests->value)
                        <a href="{{ route('payment-requests.index') }}" class="sgh-nav-item gap-2.5 {{ request()->routeIs('payment-requests.*') ? 'active' : '' }}">
                        <span class="sgh-nav-icon">
                            <x-tabler-wallet class="size-4" />
                        </span>
                        <span class="sgh-nav-title text-nowrap">{{ __('navigation.payment_requests') }}</span>
                    </a>
                    @endcan

                    @can(PermissionKey::AccessRetirementRequests->value)
                        <a href="{{ route('retirement-requests.index') }}" class="sgh-nav-item gap-2.5 {{ request()->routeIs('retirement-requests.*') ? 'active' : '' }}">
                        <span class="sgh-nav-icon">
                            <x-tabler-file-arrow-left class="size-4" />
                        </span>
                        <span class="sgh-nav-title text-nowrap">{{ __('navigation.retirements') }}</span>
                    </a>
                    @endcan
                @endcanany

                {{-- Finance --}}
                @canany([PermissionKey::DisburseRequests->value, PermissionKey::AccessCashbook->value])
                    <div class="sgh-nav-heading">
                        {{ __('navigation.sections.finance') }}
                    </div>
                @endcanany

                @can(PermissionKey::DisburseRequests->value)
                    <a href="{{ route('disbursements.index') }}" class="sgh-nav-item gap-2.5 {{ request()->routeIs('disbursements.*') ? 'active' : '' }}">
                        <span class="sgh-nav-icon">
                            <x-tabler-building-bank class="size-4" />
                        </span>
                        <span class="sgh-nav-title text-nowrap">{{ __('navigation.disbursements') }}</span>
                    </a>
                @endcan

                @can(PermissionKey::AccessCashbook->value)
                    <a href="{{ route('cashbook.branches') }}" class="sgh-nav-item gap-2.5 {{ request()->routeIs('cashbook.*') ? 'active' : '' }}">
                        <span class="sgh-nav-icon">
                            <x-tabler-calculator-filled class="size-4" />
                        </span>
                        <span class="sgh-nav-title text-nowrap">{{ __('navigation.cashbook') }}</span>
                    </a>
                @endcan

                {{-- Approvals --}}
                @can(PermissionKey::ApproveRequests->value)
                    <a href="{{ route('approvals.index') }}" class="sgh-nav-item gap-2.5 {{ request()->routeIs('approvals.*') ? 'active' : '' }}">
                        <span class="sgh-nav-icon">
                            <x-tabler-shield-check-filled class="size-4" />
                        </span>
                        <span class="sgh-nav-title text-nowrap">{{ __('navigation.approvals') }}</span>
                    </a>
                @endcan

                {{-- Reports --}}
                @can(PermissionKey::AccessReports->value)
                    <div class="sgh-nav-heading">
                        {{ __('navigation.sections.reports') }}
                    </div>

                    <a href="{{ route('reports.index') }}" class="sgh-nav-item gap-2.5 {{ request()->routeIs('reports.*') ? 'active' : '' }}">
                        <span class="sgh-nav-icon">
                            <x-tabler-chart-line class="size-4" />
                        </span>
                        <span class="sgh-nav-title text-nowrap">{{ __('navigation.reports') }}</span>
                    </a>
                @endcan

                {{-- Organisation --}}
                @canany([PermissionKey::AccessLevels->value, PermissionKey::AccessBranches->value, PermissionKey::AccessDepartments->value, PermissionKey::AccessCostCodes->value, PermissionKey::AccessPositions->value, PermissionKey::AccessStaff->value])
                    <div class="sgh-nav-heading">
                        {{ __('navigation.sections.organisation') }}
                    </div>

                    @can(PermissionKey::AccessLevels->value)
                        <a href="{{ route('levels.index') }}" class="sgh-nav-item gap-2.5 {{ request()->routeIs('levels.*') ? 'active' : '' }}">
                        <span class="sgh-nav-icon">
                            <x-tabler-layout-grid-filled class="size-4" />
                        </span>
                        <span class="sgh-nav-title text-nowrap">{{ __('navigation.levels') }}</span>
                    </a>
                    @endcan

                    @can(PermissionKey::AccessBranches->value)
                        <a href="{{ route('branches.index') }}" class="sgh-nav-item gap-2.5 {{ request()->routeIs('branches.*') ? 'active' : '' }}">
                        <span class="sgh-nav-icon">
                            <x-tabler-briefcase-filled class="size-4" />
                        </span>
                        <span class="sgh-nav-title text-nowrap">{{ __('navigation.branches') }}</span>
                    </a>
                    @endcan

                    @can(PermissionKey::AccessDepartments->value)
                        <a href="{{ route('departments.index') }}" class="sgh-nav-item gap-2.5 {{ request()->routeIs('departments.*') ? 'active' : '' }}">
                        <span class="sgh-nav-icon">
                            <x-tabler-users-group class="size-4" />
                        </span>
                        <span class="sgh-nav-title text-nowrap">{{ __('navigation.departments') }}</span>
                    </a>
                    @endcan

                    @can(PermissionKey::AccessCostCodes->value)
                        <a href="{{ route('cost-codes.index') }}" class="sgh-nav-item gap-2.5 {{ request()->routeIs('cost-codes.*') ? 'active' : '' }}">
                        <span class="sgh-nav-icon">
                            <x-tabler-book-filled class="size-4" />
                        </span>
                        <span class="sgh-nav-title text-nowrap">{{ __('navigation.cost_codes') }}</span>
                    </a>
                    @endcan

                    @can(PermissionKey::AccessPositions->value)
                        <a href="{{ route('positions.index') }}" class="sgh-nav-item gap-2.5 {{ request()->routeIs('positions.*') ? 'active' : '' }}">
                        <span class="sgh-nav-icon">
                            <x-tabler-briefcase-filled class="size-4" />
                        </span>
                        <span class="sgh-nav-title text-nowrap">{{ __('navigation.positions') }}</span>
                    </a>
                    @endcan

                    @can(PermissionKey::AccessStaff->value)
                        <a href="{{ route('staff.index') }}" class="sgh-nav-item gap-2.5 {{ request()->routeIs('staff.*') ? 'active' : '' }}">
                        <span class="sgh-nav-icon">
                            <x-tabler-users-group class="size-4" />
                        </span>
                        <span class="sgh-nav-title text-nowrap">{{ __('navigation.staff') }}</span>
                    </a>
                    @endcan
                @endcanany

                {{-- Settings --}}
                @canany([PermissionKey::AccessUsers->value, PermissionKey::AccessRoles->value, PermissionKey::AccessCurrencies->value, PermissionKey::AccessWorkflowTemplates->value, PermissionKey::AccessActivityLog->value, PermissionKey::AccessSettings->value])
                    <div class="sgh-nav-heading">
                        {{ __('navigation.sections.settings') }}
                    </div>

                    @can(PermissionKey::AccessUsers->value)
                        <a href="{{ route('users.index') }}" class="sgh-nav-item gap-2.5 {{ request()->routeIs('users.*') ? 'active' : '' }}">
                        <span class="sgh-nav-icon">
                            <x-tabler-users class="size-4" />
                        </span>
                        <span class="sgh-nav-title text-nowrap">{{ __('navigation.users') }}</span>
                    </a>
                    @endcan

                    @can(PermissionKey::AccessRoles->value)
                        <a href="{{ route('roles.index') }}" class="sgh-nav-item gap-2.5 {{ request()->routeIs('roles.*') ? 'active' : '' }}">
                        <span class="sgh-nav-icon">
                            <x-tabler-shield-check-filled class="size-4" />
                        </span>
                        <span class="sgh-nav-title text-nowrap">{{ __('navigation.roles') }}</span>
                    </a>
                    @endcan

                    @can(PermissionKey::AccessCurrencies->value)
                        <a href="{{ route('currencies.index') }}" class="sgh-nav-item gap-2.5 {{ request()->routeIs('currencies.*') ? 'active' : '' }}">
                        <span class="sgh-nav-icon">
                            <x-tabler-building-bank class="size-4" />
                        </span>
                        <span class="sgh-nav-title text-nowrap">{{ __('navigation.currencies') }}</span>
                    </a>
                    @endcan

                    @can(PermissionKey::AccessSettings->value)
                        <a href="{{ route('cash-balance-thresholds.index') }}" class="sgh-nav-item gap-2.5 {{ request()->routeIs('cash-balance-thresholds.*') ? 'active' : '' }}">
                        <span class="sgh-nav-icon">
                            <x-tabler-building-bank class="size-4" />
                        </span>
                        <span class="sgh-nav-title text-nowrap">{{ __('navigation.cash_balance_thresholds') }}</span>
                    </a>
                        <a href="{{ route('settings.index') }}" class="sgh-nav-item gap-2.5 {{ request()->routeIs('settings.*') ? 'active' : '' }}">
                        <span class="sgh-nav-icon">
                            <x-tabler-settings-filled class="size-4" />
                        </span>
                        <span class="sgh-nav-title text-nowrap">{{ __('navigation.settings') }}</span>
                    </a>
                    @endcan

                    @can(PermissionKey::AccessWorkflowTemplates->value)
                        <a href="{{ route('workflow-templates.index') }}" class="sgh-nav-item gap-2.5 {{ request()->routeIs('workflow-templates.*') ? 'active' : '' }}">
                        <span class="sgh-nav-icon">
                            <x-tabler-arrows-exchange class="size-4" />
                        </span>
                        <span class="sgh-nav-title text-nowrap">{{ __('navigation.workflows') }}</span>
                    </a>
                    @endcan

                    @can(PermissionKey::AccessActivityLog->value)
                        <a href="{{ route('activity-log.index') }}" class="sgh-nav-item gap-2.5 {{ request()->routeIs('activity-log.*') ? 'active' : '' }}">
                        <span class="sgh-nav-icon">
                            <x-tabler-clock-filled class="size-4" />
                        </span>
                        <span class="sgh-nav-title text-nowrap">{{ __('navigation.activity_log') }}</span>
                    </a>
                    @endcan
                @endcanany

            </div>
        </div>
    </div>
    <div class="hidden shrink-0 px-3 pb-4 lg:block lg:px-6">
        <div class="sgh-sidebar-help-card">
            <div class="sgh-sidebar-help-title">{{ __('navigation.need_help') }}</div>
            <div class="sgh-sidebar-help-body">
                <a class="sgh-link" href="{{ config('app.docs_url') }}" target="_blank" rel="noopener">{{ __('navigation.need_help_body') }}</a>
            </div>
        </div>
    </div>
</div>
