import { useCurrentFrame } from 'remotion';
import { IconCircleCheckFilled, IconCurrencyDollar } from '@tabler/icons-react';
import { AppShell } from '../components/AppShell';
import { Caption } from '../components/Caption';
import { Cursor } from '../components/Cursor';
import { RequestPage } from '../components/RequestPage';
import { Stage } from '../components/Stage';
import { Button, Input, Pop, Swap } from '../components/ui';
import { advance, people, tenant } from '../data';
import { centerOf, useAnchors } from '../lib/anchors';
import { typed, typingEnds } from '../lib/motion';
import { stagesAt } from '../lib/workflow';

export const DISBURSE_ADVANCE_DURATION = 240;

const REFERENCE_CLICK = 48;
const REFERENCE_START = REFERENCE_CLICK + 6;
const DISBURSE_CLICK = typingEnds(advance.disbursementReference, REFERENCE_START, 0.7) + 30;
const DISBURSED = DISBURSE_CLICK + 6;

export const DisburseAdvance = () => {
    const frame = useCurrentFrame();
    const { rootRef, anchor, rects } = useAnchors<'actions' | 'reference' | 'disburse'>();
    const disbursed = frame >= DISBURSED;

    return (
        <Stage
            url={`${tenant.host}/requests/${advance.id}`}
            camera={[
                { frame: 16, zoom: 1.55, focus: centerOf(rects.actions, { x: -60, y: 20 }), duration: 30 },
                { frame: DISBURSED + 40, zoom: 1.08, duration: 34 },
            ]}
            overlay={
                <Caption
                    step={4}
                    title="Disburse the funds"
                    body="Finance pays out and records the reference — separation of duties is enforced."
                />
            }
        >
            <AppShell
                rootRef={rootRef}
                user={people.disburser}
                active="disbursements"
                overlay={
                    <Cursor
                        start={{ x: 760, y: 560 }}
                        moves={[
                            { frame: 22, to: centerOf(rects.reference, { x: -40, y: 6 }), duration: 24 },
                            { frame: DISBURSE_CLICK - 22, to: centerOf(rects.disburse, { x: 30, y: 4 }), duration: 18 },
                        ]}
                        clicks={[REFERENCE_CLICK, DISBURSE_CLICK]}
                        hideAfter={DISBURSED + 24}
                    />
                }
            >
                <RequestPage
                    status={disbursed ? 'disbursed' : 'approved'}
                    statusChangedAt={disbursed ? DISBURSED : undefined}
                    flash={disbursed ? { message: 'Request marked as disbursed.', start: DISBURSED } : undefined}
                    submitted
                    approved
                    actionsRef={anchor('actions')}
                    stages={stagesAt(frame, 'all')}
                    actions={
                        <Swap
                            at={DISBURSED}
                            before={
                                <div className="flex flex-col gap-3">
                                    <div className="mb-2 flex items-center gap-2 rounded-lg bg-success/10 p-3 text-sm text-success">
                                        <IconCircleCheckFilled size={16} />
                                        Fully approved — awaiting disbursement
                                    </div>
                                    <div>
                                        <label className="sgh-form-label mb-1.5 block text-sm">
                                            Reference <span className="text-xs font-normal text-secondary-foreground">(optional)</span>
                                        </label>
                                        <Input
                                            inputRef={anchor('reference')}
                                            value={typed(advance.disbursementReference, frame, REFERENCE_START, 0.7)}
                                            placeholder="Transaction ref / cheque no."
                                            focused={frame >= REFERENCE_CLICK && frame < DISBURSE_CLICK - 16}
                                        />
                                    </div>
                                    <Button buttonRef={anchor('disburse')} variant="sgh-btn-success w-full" clickAt={DISBURSE_CLICK}>
                                        <IconCurrencyDollar size={16} />
                                        Mark as Disbursed
                                    </Button>
                                </div>
                            }
                            after={
                                <Pop start={DISBURSED} className="flex flex-col gap-2 rounded-lg bg-info/10 p-3 text-sm text-info">
                                    <div className="flex items-center gap-2">
                                        <IconCurrencyDollar size={16} />
                                        Disbursed on {advance.disbursedAt}
                                    </div>
                                    <span className="text-xs">Ref: {advance.disbursementReference}</span>
                                </Pop>
                            }
                        />
                    }
                />
            </AppShell>
        </Stage>
    );
};
