import type { ReactNode, Ref } from 'react';
import { Img, staticFile } from 'remotion';
import {
    IconBuildingBank,
    IconCalculatorFilled,
    IconArrowsExchange,
    IconChartLine,
    IconChevronDown,
    IconFileArrowLeft,
    IconLanguage,
    IconLayoutGridFilled,
    IconShieldCheckFilled,
    IconUsers,
    IconWallet,
} from '@tabler/icons-react';
import { tenant, type Person } from '../data';

export type NavKey =
    | 'dashboard'
    | 'payment-requests'
    | 'retirements'
    | 'disbursements'
    | 'cashbook'
    | 'approvals'
    | 'reports'
    | 'users'
    | 'roles'
    | 'workflows';

const navSections: { heading?: string; items: { key: NavKey; label: string; icon: ReactNode }[] }[] = [
    { items: [{ key: 'dashboard', label: 'Dashboard', icon: <IconLayoutGridFilled size={16} /> }] },
    {
        heading: 'Requests',
        items: [
            { key: 'payment-requests', label: 'Payment Requests', icon: <IconWallet size={16} /> },
            { key: 'retirements', label: 'Retirements', icon: <IconFileArrowLeft size={16} /> },
        ],
    },
    {
        heading: 'Finance',
        items: [
            { key: 'disbursements', label: 'Disbursements', icon: <IconBuildingBank size={16} /> },
            { key: 'cashbook', label: 'Cashbook', icon: <IconCalculatorFilled size={16} /> },
            { key: 'approvals', label: 'Approvals', icon: <IconShieldCheckFilled size={16} /> },
        ],
    },
    { heading: 'Reports', items: [{ key: 'reports', label: 'Reports', icon: <IconChartLine size={16} /> }] },
    {
        heading: 'Settings',
        items: [
            { key: 'users', label: 'Users', icon: <IconUsers size={16} /> },
            { key: 'roles', label: 'Roles', icon: <IconShieldCheckFilled size={16} /> },
            { key: 'workflows', label: 'Workflows', icon: <IconArrowsExchange size={16} /> },
        ],
    },
];

const Sidebar = ({ active, approvalsCount }: { active: NavKey; approvalsCount?: number }) => (
    <aside className="flex w-[280px] shrink-0 flex-col border-e border-border bg-background pt-3">
        <div className="flex min-h-[44px] items-center px-6">
            <Img className="default-logo" src={staticFile('flowledger_logo_light.png')} />
        </div>
        <div className="px-6 pt-4">
            <div className="sgh-sidebar-tenant-card">
                <span className="sgh-sidebar-tenant-avatar">{tenant.initials}</span>
                <span className="min-w-0 flex-1">
                    <span className="sgh-sidebar-tenant-name">{tenant.name}</span>
                    <span className="sgh-sidebar-tenant-kind">Tenant</span>
                </span>
            </div>
        </div>
        <nav className="flex grow flex-col gap-1 py-5 pe-5 ps-5">
            {navSections.map((section, index) => (
                <div key={index} className="flex flex-col gap-1">
                    {section.heading && <div className="sgh-nav-heading">{section.heading}</div>}
                    {section.items.map((item) => (
                        <div key={item.key} className={`sgh-nav-item ${item.key === active ? 'active' : ''}`}>
                            <span className="sgh-nav-icon">{item.icon}</span>
                            <span className="grow text-nowrap">{item.label}</span>
                            {item.key === 'approvals' && approvalsCount ? (
                                <span className="sgh-badge sgh-badge-sm sgh-badge-warning">{approvalsCount}</span>
                            ) : null}
                        </div>
                    ))}
                </div>
            ))}
            <div className="sgh-sidebar-help-card">
                <div className="sgh-sidebar-help-title">Need a hand?</div>
                <div className="sgh-sidebar-help-body">Docs, keyboard shortcuts and workflow guides.</div>
            </div>
        </nav>
    </aside>
);

const Header = ({ user }: { user: Person }) => (
    <header className="flex h-[70px] shrink-0 items-stretch justify-between border-b border-border bg-background px-7.5">
        <div className="flex items-center gap-2.5">
            <span className="text-lg font-semibold leading-none tracking-tight text-foreground">FlowLedger</span>
            <span className="sgh-badge sgh-badge-accent font-mono uppercase tracking-wider">Tenant</span>
        </div>
        <div className="flex items-center gap-2.5">
            <span className="sgh-btn sgh-btn-ghost sgh-btn-icon">
                <IconLanguage size={18} />
            </span>
            <div className="flex items-center gap-2.5 rounded-[var(--radius)] px-2 py-1.5">
                <span className="flex size-9 items-center justify-center rounded-full bg-accent text-xs font-semibold text-accent-foreground">
                    {user.initials}
                </span>
                <span className="flex flex-col leading-tight">
                    <span className="text-sm font-medium text-foreground">{user.name}</span>
                    <span className="text-xs text-muted-foreground">{user.role}</span>
                </span>
                <IconChevronDown size={14} className="text-muted-foreground" />
            </div>
        </div>
    </header>
);

/**
 * Static re-creation of tenant/layouts/base.blade.php: sidebar, header and
 * a scrollable content area. `scroll` is applied as a transform and exposed
 * through data-scroll so anchors can compensate for it.
 */
export const AppShell = ({
    rootRef,
    user,
    active,
    approvalsCount,
    scroll = 0,
    children,
    overlay,
}: {
    rootRef: Ref<HTMLDivElement>;
    user: Person;
    active: NavKey;
    approvalsCount?: number;
    scroll?: number;
    children: ReactNode;
    overlay?: ReactNode;
}) => (
    <div ref={rootRef} className="relative flex size-full bg-background text-base text-foreground antialiased">
        <Sidebar active={active} approvalsCount={approvalsCount} />
        <div className="flex min-w-0 grow flex-col">
            <Header user={user} />
            <main className="relative grow overflow-hidden">
                <div data-scroll={scroll} className="pt-5" style={{ transform: `translateY(${-scroll}px)` }}>
                    {children}
                </div>
            </main>
        </div>
        {overlay}
    </div>
);
