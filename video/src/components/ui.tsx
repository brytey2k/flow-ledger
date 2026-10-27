import type { CSSProperties, ReactNode, Ref } from 'react';
import { useCurrentFrame } from 'remotion';
import { IconArrowLeft, IconCheckFilled, IconCircleCheckFilled, IconClockFilled, IconDotsCircleHorizontal } from '@tabler/icons-react';
import { advance, statusBadgeClasses, statusLabels, type RequestStatus } from '../data';
import { enter, money, tween } from '../lib/motion';
import { isPressed } from './Cursor';

const CARET_BLINK_FRAMES = 16;

/**
 * Blinking text caret for focused inputs.
 */
const Caret = () => {
    const frame = useCurrentFrame();
    const visible = Math.floor(frame / CARET_BLINK_FRAMES) % 2 === 0;

    return <span className="ms-px inline-block h-[1.1em] w-px translate-y-[0.15em] bg-foreground" style={{ opacity: visible ? 1 : 0 }} />;
};

/**
 * Element scale for content that pops in at `start`.
 */
export const usePop = (start: number): { opacity: number; transform: string } => {
    const frame = useCurrentFrame();
    const progress = enter(frame, start);

    return { opacity: Math.min(1, progress * 1.5), transform: `translateY(${(1 - progress) * 14}px) scale(${0.96 + progress * 0.04})` };
};

export const Pop = ({ start, children, className }: { start: number; children: ReactNode; className?: string }) => (
    <div className={className} style={usePop(start)}>
        {children}
    </div>
);

const SwapLayer = ({ progress, collapse, children }: { progress: number; collapse: boolean; children: ReactNode }) => (
    <div
        style={{
            gridArea: '1 / 1',
            display: 'grid',
            gridTemplateRows: collapse ? `${progress}fr` : undefined,
            opacity: progress,
            visibility: progress === 0 ? 'hidden' : undefined,
        }}
    >
        <div style={collapse ? { minHeight: 0, overflow: 'clip', overflowClipMargin: 4 } : undefined}>{children}</div>
    </div>
);

/**
 * Crossfades from `before` to `after` at frame `at`, easing the height
 * between the two. Both stay mounted (stacked in one grid cell), so anchors
 * inside either are measurable on every frame — Remotion renders frames out
 * of order across tabs. Whole-page navigations pass `page`, which keeps
 * both pages at full height so a scrolled view never collapses to blank.
 */
export const Swap = ({
    at,
    before,
    after,
    duration = 10,
    page = false,
}: {
    at: number;
    before: ReactNode;
    after: ReactNode;
    duration?: number;
    page?: boolean;
}) => {
    const frame = useCurrentFrame();
    const progress = tween(frame, at, duration, 0, 1);

    return (
        <div className="grid">
            <SwapLayer progress={1 - progress} collapse={!page}>
                {before}
            </SwapLayer>
            <SwapLayer progress={progress} collapse={!page}>
                {after}
            </SwapLayer>
        </div>
    );
};

/**
 * Several pages shown in turn, each crossfading in at its `at` frame. Like
 * Swap's page mode, every page stays mounted at full height.
 */
export const PageSequence = ({ pages, duration = 10 }: { pages: { at: number; content: ReactNode }[]; duration?: number }) => {
    const frame = useCurrentFrame();

    return (
        <div className="grid">
            {pages.map((page, index) => {
                const next = pages[index + 1];
                const opacity =
                    (index === 0 ? 1 : tween(frame, page.at, duration, 0, 1)) * (next ? tween(frame, next.at, duration, 1, 0) : 1);

                return (
                    <div key={page.at} style={{ gridArea: '1 / 1', opacity, visibility: opacity === 0 ? 'hidden' : undefined }}>
                        {page.content}
                    </div>
                );
            })}
        </div>
    );
};

const focusClasses = (focused: boolean): string => (focused ? 'border-ring ring-2 ring-ring/30' : '');

export const Input = ({
    value,
    placeholder,
    focused = false,
    className = '',
    inputRef,
}: {
    value: string;
    placeholder?: string;
    focused?: boolean;
    className?: string;
    inputRef?: Ref<HTMLDivElement>;
}) => (
    <div ref={inputRef} className={`sgh-input h-[38px] whitespace-nowrap ${focusClasses(focused)} ${className}`}>
        {value ? <span>{value}</span> : !focused && <span className="text-muted-foreground">{placeholder}</span>}
        {focused && <Caret />}
    </div>
);

export const Textarea = ({
    value,
    placeholder,
    focused = false,
    rows = 3,
    textareaRef,
}: {
    value: string;
    placeholder?: string;
    focused?: boolean;
    rows?: number;
    textareaRef?: Ref<HTMLDivElement>;
}) => (
    <div ref={textareaRef} className={`sgh-textarea ${focusClasses(focused)}`} style={{ height: rows * 20 + 18 }}>
        {value ? <span>{value}</span> : !focused && <span className="text-muted-foreground">{placeholder}</span>}
        {focused && <Caret />}
    </div>
);

export const Select = ({
    value,
    placeholder,
    focused = false,
    selectRef,
}: {
    value?: string;
    placeholder: string;
    focused?: boolean;
    selectRef?: Ref<HTMLDivElement>;
}) => (
    <div ref={selectRef} className={`sgh-select flex h-[38px] items-center justify-between ${focusClasses(focused)}`}>
        <span className={value ? '' : 'text-muted-foreground'}>{value ?? placeholder}</span>
        <svg width="16" height="16" viewBox="0 0 24 24" className="text-muted-foreground">
            <path d="M6 9l6 6 6-6" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" />
        </svg>
    </div>
);

/**
 * sgh-btn with a pressed state synced to the cursor's click frame.
 */
export const Button = ({
    variant,
    children,
    clickAt,
    className = '',
    buttonRef,
}: {
    variant: string;
    children: ReactNode;
    clickAt?: number;
    className?: string;
    buttonRef?: Ref<HTMLDivElement>;
}) => {
    const frame = useCurrentFrame();
    const pressed = clickAt !== undefined && isPressed(frame, clickAt);

    return (
        <div
            ref={buttonRef}
            className={`sgh-btn ${variant} ${className}`}
            style={{ transform: pressed ? 'scale(0.96)' : undefined, opacity: pressed ? 0.85 : undefined }}
        >
            {children}
        </div>
    );
};

export const Card = ({
    title,
    aside,
    children,
    cardRef,
    bodyClassName = 'sgh-card-content p-5 lg:p-7.5 lg:pt-4',
    style,
}: {
    title: string;
    aside?: ReactNode;
    children: ReactNode;
    cardRef?: Ref<HTMLDivElement>;
    bodyClassName?: string;
    style?: CSSProperties;
}) => (
    <div ref={cardRef} className="sgh-card" style={style}>
        <div className="sgh-card-header">
            <h3 className="sgh-card-title">{title}</h3>
            {aside}
        </div>
        <div className={bodyClassName}>{children}</div>
    </div>
);

/**
 * A native checkbox, as the app renders them, with a pulse when ticked.
 */
export const Checkbox = ({
    label,
    checked,
    checkedAt,
    className = '',
    checkboxRef,
}: {
    label: string;
    checked: boolean;
    checkedAt?: number;
    className?: string;
    checkboxRef?: Ref<HTMLSpanElement>;
}) => {
    const frame = useCurrentFrame();
    const pulse = checkedAt === undefined ? 0 : tween(frame, checkedAt, 12, 1, 0) * (frame >= checkedAt ? 1 : 0);

    return (
        <label className="flex items-center gap-2">
            <span ref={checkboxRef} className="relative inline-flex">
                <input type="checkbox" checked={checked} readOnly className={className} />
                {pulse > 0 && (
                    <span
                        className="absolute -inset-1.5 rounded-full bg-primary/30"
                        style={{ opacity: pulse, transform: `scale(${1.6 - pulse * 0.6})` }}
                    />
                )}
            </span>
            <span className="text-sm text-foreground">{label}</span>
        </label>
    );
};

export const FormLabel = ({ children, required = false, small = false }: { children: ReactNode; required?: boolean; small?: boolean }) => (
    <label className={`sgh-form-label block ${small ? 'mb-1 text-xs text-secondary-foreground' : 'mb-2'}`}>
        {children} {required && <span className={small ? '' : 'text-destructive'}>*</span>}
    </label>
);

export const PageHeading = ({
    title,
    subtitle,
    badges,
    back = 'Back',
}: {
    title: string;
    subtitle: ReactNode;
    badges?: ReactNode;
    back?: string;
}) => (
    <div className="sgh-container-fixed">
        <div className="flex items-end justify-between gap-5 pb-7.5">
            <div className="flex flex-col justify-center gap-2">
                <div className="flex items-center gap-3">
                    <h1 className="text-xl font-medium leading-none">{title}</h1>
                    {badges}
                </div>
                <div className="text-sm text-secondary-foreground">{subtitle}</div>
            </div>
            <span className="sgh-btn sgh-btn-outline">
                <IconArrowLeft size={16} />
                {back}
            </span>
        </div>
    </div>
);

export const StatusBadge = ({ status, changedAt }: { status: RequestStatus; changedAt?: number }) => {
    const frame = useCurrentFrame();
    const progress = changedAt === undefined ? 1 : enter(frame, changedAt, 10);

    return (
        <span
            className={`sgh-badge sgh-badge-sm ${statusBadgeClasses[status]}`}
            style={{ transform: `scale(${0.6 + progress * 0.4})`, opacity: Math.min(1, progress * 2) }}
        >
            {statusLabels[status]}
        </span>
    );
};

export const TypeBadge = () => <span className="sgh-badge sgh-badge-sm sgh-badge-primary">Advance</span>;

export const Flash = ({ message, start }: { message: string; start: number }) => (
    <div className="sgh-container-fixed">
        <Pop start={start} className="grid gap-3 pb-5">
            <div className="sgh-alert sgh-alert-success">
                <IconCircleCheckFilled size={18} />
                {message}
            </div>
        </Pop>
    </div>
);

const DetailTerm = ({ children }: { children: ReactNode }) => (
    <dt className="mb-1 text-xs font-medium uppercase text-secondary-foreground">{children}</dt>
);

export const RequestDetailsCard = ({ submitted = false, approved = false }: { submitted?: boolean; approved?: boolean }) => (
    <Card title="Request Details">
        <dl className="grid grid-cols-2 gap-4">
            <div>
                <DetailTerm>Staff Member</DetailTerm>
                <dd className="text-sm font-medium">Ama Mensah</dd>
            </div>
            <div>
                <DetailTerm>Branch</DetailTerm>
                <dd className="text-sm">{advance.branch}</dd>
            </div>
            <div>
                <DetailTerm>Currency</DetailTerm>
                <dd className="text-sm">
                    {advance.currency.shortName} — {advance.currency.name}
                </dd>
            </div>
            <div>
                <DetailTerm>Total Amount</DetailTerm>
                <dd className="text-lg font-semibold">
                    {advance.currency.symbol} {money(advance.total)}
                </dd>
            </div>
            {submitted && (
                <div>
                    <DetailTerm>Submitted</DetailTerm>
                    <dd className="text-sm">{advance.submittedAt}</dd>
                </div>
            )}
            {approved && (
                <div>
                    <DetailTerm>Approved</DetailTerm>
                    <dd className="text-sm">{advance.approvedAt}</dd>
                </div>
            )}
            <div className="col-span-2">
                <DetailTerm>Notes</DetailTerm>
                <dd className="text-sm">{advance.notes}</dd>
            </div>
        </dl>
    </Card>
);

export const TableLabel = ({ children, end = false }: { children: ReactNode; end?: boolean }) => (
    <th className={end ? 'w-[160px] text-end' : undefined}>
        <span className={`sgh-table-col ${end ? 'justify-end' : ''}`}>
            <span className="sgh-table-col-label">{children}</span>
        </span>
    </th>
);

export const LineItemsCard = () => (
    <Card
        title="Line Items"
        aside={<span className="sgh-badge sgh-badge-sm sgh-badge-outline">{advance.items.length} items</span>}
        bodyClassName="sgh-card-table"
    >
        <table className="sgh-table sgh-table-border">
            <thead>
                <tr>
                    <TableLabel>Description</TableLabel>
                    <TableLabel>Cost Code</TableLabel>
                    <TableLabel end>Amount</TableLabel>
                </tr>
            </thead>
            <tbody>
                {advance.items.map((item) => (
                    <tr key={item.description}>
                        <td className="text-sm">{item.description}</td>
                        <td className="text-sm">—</td>
                        <td className="text-end text-sm font-medium">
                            {advance.currency.symbol} {money(item.amount)}
                        </td>
                    </tr>
                ))}
            </tbody>
            <tfoot>
                <tr>
                    <td colSpan={2} className="text-end text-sm font-medium text-secondary-foreground">
                        Total
                    </td>
                    <td className="text-end text-base font-semibold">
                        {advance.currency.symbol} {money(advance.total)}
                    </td>
                </tr>
            </tfoot>
        </table>
    </Card>
);

export type StageState = { name: string; status: 'pending' | 'active' | 'approved'; by?: string; changedAt?: number };

const StageIcon = ({ status }: { status: StageState['status'] }) => {
    if (status === 'approved') {
        return (
            <span className="flex size-6 items-center justify-center rounded-full bg-success/20 text-success">
                <IconCheckFilled size={12} />
            </span>
        );
    }

    if (status === 'active') {
        return (
            <span className="flex size-6 items-center justify-center rounded-full bg-primary/20 text-primary">
                <IconClockFilled size={12} />
            </span>
        );
    }

    return (
        <span className="flex size-6 items-center justify-center rounded-full border-2 border-border bg-background text-muted-foreground">
            <IconDotsCircleHorizontal size={12} />
        </span>
    );
};

const StageRow = ({ stage }: { stage: StageState }) => {
    const frame = useCurrentFrame();
    const progress = stage.changedAt === undefined ? 1 : enter(frame, stage.changedAt, 9);

    return (
        <div className="flex items-start gap-3">
            <div className="mt-0.5 shrink-0" style={{ transform: `scale(${0.4 + progress * 0.6})` }}>
                <StageIcon status={stage.status} />
            </div>
            <div className="flex flex-col gap-0.5">
                <span className="text-sm font-medium">{stage.name}</span>
                <span className="text-xs capitalize text-secondary-foreground">{stage.status}</span>
                {stage.status === 'active' && <span className="mt-1 text-xs text-secondary-foreground">Primary: {stage.name}</span>}
                {stage.by && <span className="text-xs text-success">Approved by {stage.by}</span>}
            </div>
        </div>
    );
};

export const ApprovalProgressCard = ({
    stages,
    cardRef,
    start,
}: {
    stages: StageState[];
    cardRef?: Ref<HTMLDivElement>;
    start?: number;
}) => (
    <Card title="Approval Progress" cardRef={cardRef} bodyClassName="sgh-card-content flex flex-col gap-3 p-5">
        {stages.map((stage, index) =>
            start === undefined ? (
                <StageRow key={stage.name} stage={stage} />
            ) : (
                <Pop key={stage.name} start={start + index * 5}>
                    <StageRow stage={stage} />
                </Pop>
            ),
        )}
    </Card>
);
