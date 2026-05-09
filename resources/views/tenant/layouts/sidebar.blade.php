@php use App\Enums\Tenant\PermissionKey; @endphp
<!-- Sidebar -->
<div
    class="kt-sidebar fixed bottom-0 top-0 z-20 hidden shrink-0 flex-col items-stretch border-e border-e-border bg-background [--kt-drawer-enable:true] lg:flex lg:[--kt-drawer-enable:false]"
    data-kt-drawer="true" data-kt-drawer-class="kt-drawer kt-drawer-start top-0 bottom-0" id="sidebar">
    <div class="kt-sidebar-header relative hidden shrink-0 items-center justify-between px-3 lg:flex lg:px-6"
         id="sidebar_header">
        <a class="dark:hidden" href="{{ route('dashboard') }}">
            <img class="default-logo min-h-[22px] max-w-none" src="{{ asset('assets/media/app/default-logo.svg') }}"/>
            <img class="small-logo min-h-[22px] max-w-none" src="{{ asset('assets/media/app/mini-logo.svg') }}"/>
        </a>
        <a class="hidden dark:block" href="{{ route('dashboard') }}">
            <img class="default-logo min-h-[22px] max-w-none" src="{{ asset('assets/media/app/default-logo-dark.svg') }}"/>
            <img class="small-logo min-h-[22px] max-w-none" src="{{ asset('assets/media/app/mini-logo.svg') }}"/>
        </a>
        <button
            class="kt-btn kt-btn-outline kt-btn-icon absolute start-full top-2/4 size-[30px] -translate-x-2/4 -translate-y-2/4 rtl:translate-x-2/4"
            data-kt-toggle="body" data-kt-toggle-class="kt-sidebar-collapse" id="sidebar_toggle">
            <i class="ki-filled ki-black-left-line kt-toggle-active:rotate-180 rtl:translate rtl:kt-toggle-active:rotate-0 transition-all duration-300 rtl:rotate-180"></i>
        </button>
    </div>
    <div class="kt-sidebar-content flex shrink-0 grow py-5 pe-2" id="sidebar_content">
        <div class="kt-scrollable-y-hover flex shrink-0 grow pe-1 ps-2 lg:pe-3 lg:ps-5" data-kt-scrollable="true"
             data-kt-scrollable-dependencies="#sidebar_header" data-kt-scrollable-height="auto"
             data-kt-scrollable-offset="0px" data-kt-scrollable-wrappers="#sidebar_content" id="sidebar_scrollable">
            <div class="kt-menu flex grow flex-col gap-1" data-kt-menu="true" data-kt-menu-accordion-expand-all="false"
                 id="sidebar_menu">

                {{-- Dashboard --}}
                <div class="kt-menu-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <a class="kt-menu-link flex grow items-center gap-[10px] border border-transparent py-[6px] pe-[10px] ps-[10px]"
                       href="{{ route('dashboard') }}">
                        <span class="kt-menu-icon w-[20px] items-start text-muted-foreground">
                            <i class="ki-filled ki-element-11 text-lg"></i>
                        </span>
                        <span class="kt-menu-title text-nowrap text-sm font-medium text-mono">Dashboard</span>
                    </a>
                </div>

                {{-- Requests --}}
                @can(PermissionKey::AccessPaymentRequests->value)
                    <div class="kt-menu-item pt-2.25 pb-px">
                        <span class="kt-menu-heading pe-[10px] ps-[10px] text-xs font-medium uppercase text-muted-foreground">
                            Requests
                        </span>
                    </div>

                    <div class="kt-menu-item {{ request()->routeIs('payment-requests.*') ? 'active' : '' }}">
                        <a class="kt-menu-link flex grow items-center gap-[10px] border border-transparent py-[6px] pe-[10px] ps-[10px]"
                           href="{{ route('payment-requests.index') }}">
                            <span class="kt-menu-icon w-[20px] items-start text-muted-foreground">
                                <i class="ki-filled ki-wallet text-lg"></i>
                            </span>
                            <span class="kt-menu-title text-nowrap text-sm font-medium text-mono">Payment Requests</span>
                        </a>
                    </div>
                @endcan

                @can(PermissionKey::AccessRetirementRequests->value)
                    <div class="kt-menu-item {{ request()->routeIs('retirement-requests.*') ? 'active' : '' }}">
                        <a class="kt-menu-link flex grow items-center gap-[10px] border border-transparent py-[6px] pe-[10px] ps-[10px]"
                           href="{{ route('retirement-requests.index') }}">
                            <span class="kt-menu-icon w-[20px] items-start text-muted-foreground">
                                <i class="ki-filled ki-file-up text-lg"></i>
                            </span>
                            <span class="kt-menu-title text-nowrap text-sm font-medium text-mono">Retirements</span>
                        </a>
                    </div>
                @endcan

                {{-- Finance --}}
                @can(PermissionKey::DisburseRequests->value)
                    <div class="kt-menu-item pt-2.25 pb-px">
                        <span class="kt-menu-heading pe-[10px] ps-[10px] text-xs font-medium uppercase text-muted-foreground">
                            Finance
                        </span>
                    </div>

                    <div class="kt-menu-item {{ request()->routeIs('disbursements.*') ? 'active' : '' }}">
                        <a class="kt-menu-link flex grow items-center gap-[10px] border border-transparent py-[6px] pe-[10px] ps-[10px]"
                           href="{{ route('disbursements.index') }}">
                            <span class="kt-menu-icon w-[20px] items-start text-muted-foreground">
                                <i class="ki-filled ki-dollar text-lg"></i>
                            </span>
                            <span class="kt-menu-title text-nowrap text-sm font-medium text-mono">Disbursements</span>
                        </a>
                    </div>
                @endcan

                {{-- Approvals --}}
                @can(PermissionKey::ApproveRequests->value)
                    <div class="kt-menu-item {{ request()->routeIs('approvals.*') ? 'active' : '' }}">
                        <a class="kt-menu-link flex grow items-center gap-[10px] border border-transparent py-[6px] pe-[10px] ps-[10px]"
                           href="{{ route('approvals.index') }}">
                            <span class="kt-menu-icon w-[20px] items-start text-muted-foreground">
                                <i class="ki-filled ki-shield-tick text-lg"></i>
                            </span>
                            <span class="kt-menu-title text-nowrap text-sm font-medium text-mono">Approvals</span>
                        </a>
                    </div>
                @endcan

                {{-- Organisation --}}
                @canany([PermissionKey::AccessLevels->value, PermissionKey::AccessBranches->value, PermissionKey::AccessDepartments->value, PermissionKey::AccessAccountCodes->value, PermissionKey::AccessPositions->value, PermissionKey::AccessStaff->value])
                    <div class="kt-menu-item pt-2.25 pb-px">
                        <span class="kt-menu-heading pe-[10px] ps-[10px] text-xs font-medium uppercase text-muted-foreground">
                            Organisation
                        </span>
                    </div>

                    @can(PermissionKey::AccessLevels->value)
                        <div class="kt-menu-item {{ request()->routeIs('levels.*') ? 'active' : '' }}">
                            <a class="kt-menu-link flex grow items-center gap-[10px] border border-transparent py-[6px] pe-[10px] ps-[10px]"
                               href="{{ route('levels.index') }}">
                                <span class="kt-menu-icon w-[20px] items-start text-muted-foreground">
                                    <i class="ki-filled ki-layers text-lg"></i>
                                </span>
                                <span class="kt-menu-title text-nowrap text-sm font-medium text-mono">Levels</span>
                            </a>
                        </div>
                    @endcan

                    @can(PermissionKey::AccessBranches->value)
                        <div class="kt-menu-item {{ request()->routeIs('branches.*') ? 'active' : '' }}">
                            <a class="kt-menu-link flex grow items-center gap-[10px] border border-transparent py-[6px] pe-[10px] ps-[10px]"
                               href="{{ route('branches.index') }}">
                                <span class="kt-menu-icon w-[20px] items-start text-muted-foreground">
                                    <i class="ki-filled ki-office-bag text-lg"></i>
                                </span>
                                <span class="kt-menu-title text-nowrap text-sm font-medium text-mono">Branches</span>
                            </a>
                        </div>
                    @endcan

                    @can(PermissionKey::AccessDepartments->value)
                        <div class="kt-menu-item {{ request()->routeIs('departments.*') ? 'active' : '' }}">
                            <a class="kt-menu-link flex grow items-center gap-[10px] border border-transparent py-[6px] pe-[10px] ps-[10px]"
                               href="{{ route('departments.index') }}">
                                <span class="kt-menu-icon w-[20px] items-start text-muted-foreground">
                                    <i class="ki-filled ki-people text-lg"></i>
                                </span>
                                <span class="kt-menu-title text-nowrap text-sm font-medium text-mono">Departments</span>
                            </a>
                        </div>
                    @endcan

                    @can(PermissionKey::AccessAccountCodes->value)
                        <div class="kt-menu-item {{ request()->routeIs('account-codes.*') ? 'active' : '' }}">
                            <a class="kt-menu-link flex grow items-center gap-[10px] border border-transparent py-[6px] pe-[10px] ps-[10px]"
                               href="{{ route('account-codes.index') }}">
                                <span class="kt-menu-icon w-[20px] items-start text-muted-foreground">
                                    <i class="ki-filled ki-book text-lg"></i>
                                </span>
                                <span class="kt-menu-title text-nowrap text-sm font-medium text-mono">Account Codes</span>
                            </a>
                        </div>
                    @endcan

                    @can(PermissionKey::AccessPositions->value)
                        <div class="kt-menu-item {{ request()->routeIs('positions.*') ? 'active' : '' }}">
                            <a class="kt-menu-link flex grow items-center gap-[10px] border border-transparent py-[6px] pe-[10px] ps-[10px]"
                               href="{{ route('positions.index') }}">
                                <span class="kt-menu-icon w-[20px] items-start text-muted-foreground">
                                    <i class="ki-filled ki-briefcase text-lg"></i>
                                </span>
                                <span class="kt-menu-title text-nowrap text-sm font-medium text-mono">Positions</span>
                            </a>
                        </div>
                    @endcan

                    @can(PermissionKey::AccessStaff->value)
                        <div class="kt-menu-item {{ request()->routeIs('staff.*') ? 'active' : '' }}">
                            <a class="kt-menu-link flex grow items-center gap-[10px] border border-transparent py-[6px] pe-[10px] ps-[10px]"
                               href="{{ route('staff.index') }}">
                                <span class="kt-menu-icon w-[20px] items-start text-muted-foreground">
                                    <i class="ki-filled ki-people text-lg"></i>
                                </span>
                                <span class="kt-menu-title text-nowrap text-sm font-medium text-mono">Staff</span>
                            </a>
                        </div>
                    @endcan
                @endcanany

                {{-- Settings --}}
                @canany([PermissionKey::AccessUsers->value, PermissionKey::AccessRoles->value, PermissionKey::AccessCurrencies->value, PermissionKey::AccessWorkflowTemplates->value, PermissionKey::AccessActivityLog->value])
                    <div class="kt-menu-item pt-2.25 pb-px">
                        <span class="kt-menu-heading pe-[10px] ps-[10px] text-xs font-medium uppercase text-muted-foreground">
                            Settings
                        </span>
                    </div>

                    @can(PermissionKey::AccessUsers->value)
                        <div class="kt-menu-item {{ request()->routeIs('users.*') ? 'active' : '' }}">
                            <a class="kt-menu-link flex grow items-center gap-[10px] border border-transparent py-[6px] pe-[10px] ps-[10px]"
                               href="{{ route('users.index') }}">
                                <span class="kt-menu-icon w-[20px] items-start text-muted-foreground">
                                    <i class="ki-filled ki-profile-user text-lg"></i>
                                </span>
                                <span class="kt-menu-title text-nowrap text-sm font-medium text-mono">Users</span>
                            </a>
                        </div>
                    @endcan

                    @can(PermissionKey::AccessRoles->value)
                        <div class="kt-menu-item {{ request()->routeIs('roles.*') ? 'active' : '' }}">
                            <a class="kt-menu-link flex grow items-center gap-[10px] border border-transparent py-[6px] pe-[10px] ps-[10px]"
                               href="{{ route('roles.index') }}">
                                <span class="kt-menu-icon w-[20px] items-start text-muted-foreground">
                                    <i class="ki-filled ki-security-user text-lg"></i>
                                </span>
                                <span class="kt-menu-title text-nowrap text-sm font-medium text-mono">Roles</span>
                            </a>
                        </div>
                    @endcan

                    @can(PermissionKey::AccessCurrencies->value)
                        <div class="kt-menu-item {{ request()->routeIs('currencies.*') ? 'active' : '' }}">
                            <a class="kt-menu-link flex grow items-center gap-[10px] border border-transparent py-[6px] pe-[10px] ps-[10px]"
                               href="{{ route('currencies.index') }}">
                                <span class="kt-menu-icon w-[20px] items-start text-muted-foreground">
                                    <i class="ki-filled ki-dollar text-lg"></i>
                                </span>
                                <span class="kt-menu-title text-nowrap text-sm font-medium text-mono">Currencies</span>
                            </a>
                        </div>
                    @endcan

                    @can(PermissionKey::AccessWorkflowTemplates->value)
                        <div class="kt-menu-item {{ request()->routeIs('workflow-templates.*') ? 'active' : '' }}">
                            <a class="kt-menu-link flex grow items-center gap-[10px] border border-transparent py-[6px] pe-[10px] ps-[10px]"
                               href="{{ route('workflow-templates.index') }}">
                                <span class="kt-menu-icon w-[20px] items-start text-muted-foreground">
                                    <i class="ki-filled ki-arrow-right-left text-lg"></i>
                                </span>
                                <span class="kt-menu-title text-nowrap text-sm font-medium text-mono">Workflows</span>
                            </a>
                        </div>
                    @endcan

                    @can(PermissionKey::AccessActivityLog->value)
                        <div class="kt-menu-item {{ request()->routeIs('activity-log.*') ? 'active' : '' }}">
                            <a class="kt-menu-link flex grow items-center gap-[10px] border border-transparent py-[6px] pe-[10px] ps-[10px]"
                               href="{{ route('activity-log.index') }}">
                                <span class="kt-menu-icon w-[20px] items-start text-muted-foreground">
                                    <i class="ki-filled ki-time text-lg"></i>
                                </span>
                                <span class="kt-menu-title text-nowrap text-sm font-medium text-mono">Activity Log</span>
                            </a>
                        </div>
                    @endcan
                @endcanany

            </div>
        </div>
    </div>
</div>
