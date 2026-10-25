import { useCurrentFrame } from 'remotion';
import { IconCheckFilled, IconCurrencyDollar, IconFileArrowLeft, IconTrashFilled } from '@tabler/icons-react';
import { AppShell } from '../components/AppShell';
import { Caption } from '../components/Caption';
import { Cursor } from '../components/Cursor';
import { RequestPage } from '../components/RequestPage';
import { Stage } from '../components/Stage';
import { Button, Card, Flash, Input, PageHeading, Pop, Select, Swap, Textarea, usePop } from '../components/ui';
import { advance, people, retirement, tenant } from '../data';
import { centerOf, useAnchors } from '../lib/anchors';
import { countUp, money, tween, typed, typingEnds } from '../lib/motion';
import { stagesAt } from '../lib/workflow';

const RETIRE_CLICK = 44;
const RETIRE_PAGE = RETIRE_CLICK + 8;
const ITEMS_START = RETIRE_PAGE + 40;
const ROW_HEIGHT = 92;

type RowTiming = {
    rowAt: number;
    descriptionAt: number;
    costCodeAt: number;
    amountAt: number;
    amountDone: number;
    receiptAt: number;
    done: number;
};

const rowTimings: RowTiming[] = retirement.items.reduce<RowTiming[]>((timings, item, index) => {
    const rowAt = index === 0 ? ITEMS_START : timings[index - 1].done + 6;
    const descriptionAt = rowAt + 6;
    const costCodeAt = typingEnds(item.description, descriptionAt, 1.8) + 4;
    const amountAt = costCodeAt + 8;
    const amountDone = typingEnds(String(item.amount), amountAt, 0.6);
    const receiptAt = amountDone + 4;
    const done = typingEnds(item.receipt, receiptAt, 1.2);

    return [...timings, { rowAt, descriptionAt, costCodeAt, amountAt, amountDone, receiptAt, done }];
}, []);

const ITEMS_DONE = rowTimings[rowTimings.length - 1].done;
const DIFFERENCE_AT = rowTimings[rowTimings.length - 1].amountDone + 16;
const SAVE_CLICK = DIFFERENCE_AT + 70;
const SAVED = SAVE_CLICK + 6;

export const RETIRE_ADVANCE_DURATION = SAVED + 60;

const ExpenditureRow = ({ index, frame }: { index: number; frame: number }) => {
    const item = retirement.items[index];
    const timing = rowTimings[index];
    const style = usePop(timing.rowAt);
    const height = tween(frame, timing.rowAt, 10, 0, ROW_HEIGHT + 16);

    return (
        <div style={index === 0 ? undefined : { height, overflow: 'hidden' }}>
            <div
                className={`flex items-start gap-3 rounded-lg border border-border p-4 ${index === 0 ? '' : 'mt-4'}`}
                style={index === 0 ? undefined : style}
            >
                <div className="flex-1">
                    <label className="sgh-form-label mb-1.5 block text-sm">
                        Description <span className="text-destructive">*</span>
                    </label>
                    <Input
                        value={typed(item.description, frame, timing.descriptionAt, 1.8)}
                        placeholder="What was purchased"
                        focused={frame >= timing.descriptionAt && frame < timing.costCodeAt}
                    />
                </div>
                <div className="w-64">
                    <label className="sgh-form-label mb-1.5 block text-sm">
                        Cost Code <span className="text-destructive">*</span>
                    </label>
                    <Select
                        value={frame >= timing.costCodeAt ? item.costCode : undefined}
                        placeholder="Select…"
                        focused={frame >= timing.costCodeAt && frame < timing.amountAt}
                    />
                </div>
                <div className="w-36">
                    <label className="sgh-form-label mb-1.5 block text-sm">
                        Amount <span className="text-destructive">*</span>
                    </label>
                    <Input
                        value={typed(String(item.amount), frame, timing.amountAt, 0.6)}
                        placeholder="0.00"
                        focused={frame >= timing.amountAt && frame < timing.receiptAt}
                    />
                </div>
                <div className="w-36">
                    <label className="sgh-form-label mb-1.5 block text-sm">Receipt No.</label>
                    <Input
                        value={typed(item.receipt, frame, timing.receiptAt, 1.2)}
                        placeholder="Optional"
                        focused={frame >= timing.receiptAt && frame < timing.done + 4}
                    />
                </div>
                <div className="mt-7 shrink-0">
                    <span className="sgh-btn sgh-btn-icon sgh-btn-sm sgh-btn-danger">
                        <IconTrashFilled size={14} />
                    </span>
                </div>
            </div>
        </div>
    );
};

const totalExpended = (frame: number): number =>
    retirement.items.reduce((total, item, index) => total + countUp(frame, rowTimings[index].amountDone, 14, 0, item.amount), 0);

const RetirementForm = ({
    frame,
    summaryRef,
    saveRef,
    itemsRef,
}: {
    frame: number;
    summaryRef: (element: HTMLDivElement | null) => void;
    saveRef: (element: HTMLDivElement | null) => void;
    itemsRef: (element: HTMLDivElement | null) => void;
}) => (
    <>
        {frame >= SAVED && <Flash message="Retirement saved as draft." start={SAVED} />}
        <PageHeading
            title={`Retire Request #${advance.id}`}
            subtitle={`Record actual expenditure for ${people.staff.name}`}
            back="Back to Request"
        />
        <div className="sgh-container-fixed">
            <div className="flex flex-col gap-7.5">
                <Card title="Advance Summary" bodyClassName="sgh-card-content p-5">
                    <dl className="grid grid-cols-3 gap-4">
                        <div>
                            <dt className="mb-1 text-xs font-medium uppercase text-secondary-foreground">Staff</dt>
                            <dd className="text-sm font-medium">{people.staff.name}</dd>
                        </div>
                        <div>
                            <dt className="mb-1 text-xs font-medium uppercase text-secondary-foreground">Branch</dt>
                            <dd className="text-sm">{advance.branch}</dd>
                        </div>
                        <div>
                            <dt className="mb-1 text-xs font-medium uppercase text-secondary-foreground">Advance Amount</dt>
                            <dd className="text-lg font-semibold">
                                {advance.currency.symbol} {money(advance.total)}
                            </dd>
                        </div>
                    </dl>
                </Card>

                <Card title="Expenditure Items" cardRef={itemsRef}>
                    <div className="flex flex-col">
                        {retirement.items.map((item, index) => (
                            <ExpenditureRow key={item.description} index={index} frame={frame} />
                        ))}
                    </div>
                </Card>

                <div className="grid grid-cols-3 gap-7.5 pb-7.5">
                    <Card title="Notes" bodyClassName="sgh-card-content p-5" style={{ gridColumn: 'span 2' }}>
                        <Textarea value="" placeholder="Optional notes…" />
                    </Card>
                    <Card title="Summary" cardRef={summaryRef} bodyClassName="sgh-card-content flex flex-col gap-4 p-5">
                        <div className="flex justify-between text-sm">
                            <span className="text-secondary-foreground">Advance Amount</span>
                            <span className="font-medium">
                                {advance.currency.symbol} {money(advance.total)}
                            </span>
                        </div>
                        <div className="flex justify-between text-sm">
                            <span className="text-secondary-foreground">Total Expended</span>
                            <span className="font-medium">
                                {advance.currency.symbol} {money(totalExpended(frame))}
                            </span>
                        </div>
                        <hr className="border-border" />
                        <div className="flex justify-between text-sm">
                            <span className="text-secondary-foreground">Difference</span>
                            <span className={`font-semibold ${frame >= DIFFERENCE_AT ? 'text-destructive' : ''}`}>
                                {frame >= DIFFERENCE_AT ? `${advance.currency.symbol} ${money(retirement.difference)}` : '—'}
                            </span>
                        </div>
                        <div className="h-4 text-xs text-secondary-foreground">
                            {frame >= DIFFERENCE_AT && <Pop start={DIFFERENCE_AT}>{retirement.differenceLabel}</Pop>}
                        </div>
                        <Button buttonRef={saveRef} variant="sgh-btn-primary mt-2 w-full" clickAt={SAVE_CLICK}>
                            <IconCheckFilled size={16} />
                            Save as Draft
                        </Button>
                    </Card>
                </div>
            </div>
        </div>
    </>
);

export const RetireAdvance = () => {
    const frame = useCurrentFrame();
    const { rootRef, anchor, rects } = useAnchors<'retire' | 'items' | 'summary' | 'save'>();
    const scroll = frame < RETIRE_PAGE ? 0 : tween(frame, ITEMS_DONE - 30, 34, 0, 300);

    return (
        <Stage
            url={frame >= RETIRE_PAGE ? `${tenant.host}/requests/${advance.id}/retirement/create` : `${tenant.host}/requests/${advance.id}`}
            camera={[
                { frame: 6, zoom: 1.45, focus: centerOf(rects.retire, { x: -60, y: 40 }), duration: 26 },
                { frame: RETIRE_PAGE, zoom: 1, duration: 22 },
                { frame: ITEMS_START - 10, zoom: 1.3, focus: centerOf(rects.items, { x: 0, y: 30 }), duration: 30 },
                { frame: ITEMS_DONE - 30, zoom: 1.5, focus: centerOf(rects.summary, { x: -140, y: 0 }), duration: 34 },
            ]}
            overlay={
                <Caption
                    step={5}
                    title="Retire the advance"
                    body="Staff account for every cedi spent. FlowLedger works out the refund or top-up."
                />
            }
        >
            <AppShell
                rootRef={rootRef}
                user={people.staff}
                active="payment-requests"
                scroll={scroll}
                overlay={
                    <Cursor
                        start={{ x: 820, y: 600 }}
                        moves={[
                            { frame: 12, to: centerOf(rects.retire, { x: 30, y: 4 }), duration: 24 },
                            { frame: ITEMS_START - 6, to: centerOf(rects.items, { x: 120, y: 120 }), duration: 26 },
                            { frame: SAVE_CLICK - 26, to: centerOf(rects.save, { x: 40, y: 4 }), duration: 20 },
                        ]}
                        clicks={[RETIRE_CLICK, SAVE_CLICK]}
                        hideAfter={SAVED + 4}
                    />
                }
            >
                <Swap
                    at={RETIRE_PAGE}
                    before={
                        <RequestPage
                            status="disbursed"
                            submitted
                            approved
                            stages={stagesAt(frame, 'all')}
                            actions={
                                <>
                                    <Button buttonRef={anchor('retire')} variant="sgh-btn-primary w-full" clickAt={RETIRE_CLICK}>
                                        <IconFileArrowLeft size={16} />
                                        Retire this Advance
                                    </Button>
                                    <div className="flex flex-col gap-2 rounded-lg bg-info/10 p-3 text-sm text-info">
                                        <div className="flex items-center gap-2">
                                            <IconCurrencyDollar size={16} />
                                            Disbursed on {advance.disbursedAt}
                                        </div>
                                        <span className="text-xs">Ref: {advance.disbursementReference}</span>
                                    </div>
                                </>
                            }
                        />
                    }
                    after={
                        <RetirementForm frame={frame} itemsRef={anchor('items')} summaryRef={anchor('summary')} saveRef={anchor('save')} />
                    }
                />
            </AppShell>
        </Stage>
    );
};
