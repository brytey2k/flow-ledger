import { useCurrentFrame } from 'remotion';
import { IconArrowLeft, IconCurrencyDollar, IconDeviceFloppy, IconPlusFilled, IconTrashFilled, IconUserFilled } from '@tabler/icons-react';
import { AppShell } from '../components/AppShell';
import { Caption } from '../components/Caption';
import { Cursor } from '../components/Cursor';
import { Stage } from '../components/Stage';
import { Button, Card, FormLabel, Input, Select, Textarea, usePop } from '../components/ui';
import { advance, people, tenant } from '../data';
import { centerOf, useAnchors } from '../lib/anchors';
import { countUp, money, tween, typed, typingEnds } from '../lib/motion';

export const CREATE_ADVANCE_DURATION = 360;

const TYPE_CLICK = 34;
const OPTION_CLICK = 62;
const NOTES_START = 92;
const ITEMS_START = 160;
const ADD_ITEM_GAP = 12;
const SAVE_CLICK = 330;
const ROW_HEIGHT = 102;

type ItemTiming = { rowAt: number; descriptionAt: number; amountAt: number; amountDone: number; addClick?: number };

/**
 * Each line item: the row appears, its description and amount are typed,
 * then "Add Item" is clicked for the next one.
 */
const itemTimings: ItemTiming[] = advance.items.reduce<ItemTiming[]>((timings, item, index) => {
    const rowAt = index === 0 ? ITEMS_START : (timings[index - 1].addClick as number) + 4;
    const descriptionAt = rowAt + 6;
    const amountAt = typingEnds(item.description, descriptionAt, 1.4) + 6;
    const amountDone = typingEnds(String(item.amount), amountAt, 0.5);
    const addClick = index < advance.items.length - 1 ? amountDone + ADD_ITEM_GAP : undefined;

    return [...timings, { rowAt, descriptionAt, amountAt, amountDone, addClick }];
}, []);

const ItemRow = ({
    index,
    frame,
    descriptionRef,
    amountRef,
}: {
    index: number;
    frame: number;
    descriptionRef?: (el: HTMLDivElement | null) => void;
    amountRef?: (el: HTMLDivElement | null) => void;
}) => {
    const item = advance.items[index];
    const timing = itemTimings[index];
    const style = usePop(timing.rowAt);
    const height = tween(frame, timing.rowAt, 10, 0, ROW_HEIGHT + 16);
    const typingDescription = frame >= timing.descriptionAt && frame < timing.amountAt;
    const typingAmount = frame >= timing.amountAt && frame < timing.amountDone + 8;

    return (
        <div style={index === 0 ? undefined : { height, overflow: 'hidden' }}>
            <div
                className={`flex flex-col gap-3 rounded-lg border border-border p-4 ${index === 0 ? '' : 'mt-4'}`}
                style={index === 0 ? undefined : style}
            >
                <div className="flex items-start gap-3">
                    <div className="min-w-0 flex-1">
                        <FormLabel small required>
                            Description
                        </FormLabel>
                        <Input
                            inputRef={descriptionRef}
                            value={typed(item.description, frame, timing.descriptionAt, 1.4)}
                            placeholder={index === 0 ? 'e.g. Transport to Accra' : 'e.g. Accommodation'}
                            focused={typingDescription}
                        />
                    </div>
                    <div className="w-44 shrink-0">
                        <FormLabel small required>
                            Amount
                        </FormLabel>
                        <Input
                            inputRef={amountRef}
                            value={typed(String(item.amount), frame, timing.amountAt, 0.5)}
                            placeholder="0.00"
                            focused={typingAmount}
                        />
                    </div>
                    <div className="mt-5 shrink-0">
                        {index > 0 ? (
                            <span className="sgh-btn sgh-btn-icon sgh-btn-sm sgh-btn-danger">
                                <IconTrashFilled size={14} />
                            </span>
                        ) : (
                            <div className="w-9" />
                        )}
                    </div>
                </div>
            </div>
        </div>
    );
};

const runningTotal = (frame: number): number =>
    advance.items.reduce((total, item, index) => {
        const { amountDone } = itemTimings[index];

        return total + countUp(frame, amountDone, 14, 0, item.amount);
    }, 0);

export const CreateAdvance = () => {
    const frame = useCurrentFrame();
    const { rootRef, anchor, rects } = useAnchors<
        'type' | 'advanceOption' | 'notes' | 'addItem' | 'save' | 'details' | 'items' | `description${number}` | `amount${number}`
    >();

    const dropdownOpen = frame >= TYPE_CLICK + 2 && frame < OPTION_CLICK + 4;
    const typeChosen = frame >= OPTION_CLICK + 2;
    const dropdownStyle = usePop(TYPE_CLICK + 2);
    const notesFocused = frame >= NOTES_START - 6 && frame < ITEMS_START - 10;
    const scroll = tween(frame, ITEMS_START - 24, 30, 0, 250) + tween(frame, SAVE_CLICK - 50, 24, 0, 140);
    const lastAddClick = itemTimings[itemTimings.length - 2].addClick as number;

    const cursorMoves = [
        { frame: 12, to: centerOf(rects.type) },
        { frame: TYPE_CLICK + 8, to: centerOf(rects.advanceOption, { x: -60, y: 0 }) },
        { frame: OPTION_CLICK + 10, to: centerOf(rects.notes, { x: -120, y: -10 }), duration: 16 },
        ...itemTimings.flatMap((timing, index) => [
            { frame: timing.descriptionAt - 8, to: centerOf(rects[`description${index}`], { x: 80, y: 6 }), duration: 12 },
            { frame: timing.amountAt - 6, to: centerOf(rects[`amount${index}`], { x: 30, y: 6 }), duration: 10 },
            ...(timing.addClick ? [{ frame: timing.addClick - 12, to: centerOf(rects.addItem), duration: 11 }] : []),
        ]),
        { frame: SAVE_CLICK - 26, to: centerOf(rects.save), duration: 20 },
    ];

    return (
        <Stage
            url={`${tenant.host}/requests/create`}
            camera={[
                { frame: 14, zoom: 1.35, focus: centerOf(rects.details, { x: 0, y: -40 }), duration: 26 },
                { frame: ITEMS_START - 24, zoom: 1.3, focus: centerOf(rects.items, { x: 0, y: -60 }), duration: 30 },
                { frame: lastAddClick + 10, zoom: 1.3, focus: centerOf(rects.items, { x: 0, y: 40 }), duration: 30 },
                { frame: SAVE_CLICK - 50, zoom: 1.12, focus: centerOf(rects.save, { x: 300, y: -160 }), duration: 30 },
            ]}
            overlay={
                <Caption
                    step={1}
                    title="Raise an advance"
                    body="Staff request funds before they spend, line by line, in their branch currency."
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
                        start={{ x: 900, y: 700 }}
                        moves={cursorMoves}
                        clicks={[
                            TYPE_CLICK,
                            OPTION_CLICK,
                            NOTES_START - 8,
                            ...itemTimings.flatMap((timing) => (timing.addClick ? [timing.addClick] : [])),
                            SAVE_CLICK,
                        ]}
                    />
                }
            >
                <div className="sgh-container-fixed">
                    <div className="flex items-end justify-between gap-5 pb-7.5">
                        <div className="flex flex-col justify-center gap-2">
                            <h1 className="text-xl font-medium leading-none">New Payment Request</h1>
                            <div className="text-sm text-secondary-foreground">Submit an advance or expense reimbursement request</div>
                        </div>
                        <span className="sgh-btn sgh-btn-outline">
                            <IconArrowLeft size={16} />
                            Back to Requests
                        </span>
                    </div>
                </div>

                <div className="sgh-container-fixed">
                    <div className="grid gap-7.5">
                        <Card title="Request Details" cardRef={anchor('details')}>
                            <div className="grid grid-cols-3 gap-5">
                                <div className="relative">
                                    <FormLabel required>Request Type</FormLabel>
                                    <Select
                                        selectRef={anchor('type')}
                                        value={typeChosen ? 'Advance — request funds before spending' : undefined}
                                        placeholder="Select type…"
                                        focused={dropdownOpen}
                                    />
                                    <div
                                        className="sgh-dropdown-panel start-0 end-auto z-20 w-full"
                                        style={{ opacity: dropdownOpen ? dropdownStyle.opacity : 0, transform: dropdownStyle.transform }}
                                    >
                                        <div className="sgh-dropdown-item text-muted-foreground">Select type…</div>
                                        <div
                                            ref={anchor('advanceOption')}
                                            className={`sgh-dropdown-item ${frame >= OPTION_CLICK - 14 ? 'bg-accent text-accent-foreground' : ''}`}
                                        >
                                            Advance — request funds before spending
                                        </div>
                                        <div className="sgh-dropdown-item">Expense — reimburse money already spent</div>
                                    </div>
                                </div>
                                <div>
                                    <FormLabel>Submitting As</FormLabel>
                                    <div className="sgh-input flex items-center gap-3 rounded-md bg-muted/40 px-4 py-3">
                                        <IconUserFilled size={16} className="shrink-0 text-secondary-foreground" />
                                        <div className="truncate">
                                            <span className="font-medium">{people.staff.name}</span>
                                            <span className="text-secondary-foreground"> · {advance.department}</span>
                                            <span className="text-secondary-foreground"> · {advance.branch}</span>
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <FormLabel>Currency</FormLabel>
                                    <div className="sgh-input flex items-center gap-3 rounded-md bg-muted/40 px-4 py-3">
                                        <IconCurrencyDollar size={16} className="text-secondary-foreground" />
                                        <span className="font-medium">
                                            {advance.currency.shortName} — {advance.currency.name}
                                        </span>
                                    </div>
                                </div>
                                <div className="col-span-3">
                                    <FormLabel>Notes</FormLabel>
                                    <Textarea
                                        textareaRef={anchor('notes')}
                                        value={typed(advance.notes, frame, NOTES_START, 1.4)}
                                        placeholder="Optional: provide context or justification for this request…"
                                        focused={notesFocused}
                                    />
                                </div>
                            </div>
                        </Card>

                        <Card
                            title="Line Items"
                            cardRef={anchor('items')}
                            aside={<span className="text-sm text-secondary-foreground">Add each item to be funded or reimbursed</span>}
                        >
                            <div className="flex flex-col">
                                {advance.items.map((_, index) => (
                                    <ItemRow
                                        key={index}
                                        index={index}
                                        frame={frame}
                                        descriptionRef={anchor(`description${index}`)}
                                        amountRef={anchor(`amount${index}`)}
                                    />
                                ))}
                            </div>
                            <div className="mt-5 flex items-center justify-between border-t border-border pt-4">
                                <Button
                                    buttonRef={anchor('addItem')}
                                    variant="sgh-btn-sm sgh-btn-outline"
                                    clickAt={
                                        itemTimings.find((timing) => timing.addClick !== undefined && frame < timing.addClick + 10)
                                            ?.addClick
                                    }
                                >
                                    <IconPlusFilled size={14} />
                                    Add Item
                                </Button>
                                <div className="flex items-center gap-3">
                                    <span className="text-sm text-secondary-foreground">Total:</span>
                                    <span className="text-lg font-semibold">{money(runningTotal(frame))}</span>
                                </div>
                            </div>
                        </Card>

                        <div className="flex items-center gap-2.5 pb-7.5">
                            <Button buttonRef={anchor('save')} variant="sgh-btn-primary" clickAt={SAVE_CLICK}>
                                <IconDeviceFloppy size={16} />
                                Save as Draft
                            </Button>
                            <span className="sgh-btn sgh-btn-light">Cancel</span>
                        </div>
                    </div>
                </div>
            </AppShell>
        </Stage>
    );
};
