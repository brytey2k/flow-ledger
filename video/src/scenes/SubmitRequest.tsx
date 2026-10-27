import { useCurrentFrame } from 'remotion';
import { IconClockFilled, IconPencilFilled, IconSendFilled, IconXFilled } from '@tabler/icons-react';
import { AppShell } from '../components/AppShell';
import { Cursor } from '../components/Cursor';
import { RequestPage } from '../components/RequestPage';
import { Stage } from '../components/Stage';
import { Button, Pop, Swap } from '../components/ui';
import { advance, people, tenant, workflowStages } from '../data';
import { centerOf, useAnchors } from '../lib/anchors';

export const SUBMIT_REQUEST_DURATION = 230;

const SUBMIT_CLICK = 62;
const SUBMITTED = SUBMIT_CLICK + 6;

export const SubmitRequest = () => {
    const frame = useCurrentFrame();
    const { rootRef, anchor, rects } = useAnchors<'actions' | 'submit' | 'progress'>();
    const submitted = frame >= SUBMITTED;

    return (
        <Stage
            url={`${tenant.host}/requests/${advance.id}`}
            camera={[
                { frame: 20, zoom: 1.5, focus: centerOf(rects.actions, { x: -60, y: 30 }), duration: 30 },
                { frame: SUBMITTED + 18, zoom: 1.45, focus: centerOf(rects.progress, { x: -80, y: -40 }), duration: 30 },
            ]}
        >
            <AppShell
                rootRef={rootRef}
                user={people.staff}
                active="payment-requests"
                overlay={
                    <Cursor
                        start={{ x: 760, y: 520 }}
                        moves={[{ frame: 28, to: centerOf(rects.submit, { x: 20, y: 4 }), duration: 26 }]}
                        clicks={[SUBMIT_CLICK]}
                        hideAfter={SUBMITTED + 30}
                    />
                }
            >
                <RequestPage
                    status={submitted ? 'in_workflow' : 'draft'}
                    statusChangedAt={submitted ? SUBMITTED : undefined}
                    flash={
                        submitted
                            ? { message: 'Request submitted for approval.', start: SUBMITTED }
                            : { message: 'Request saved as draft.', start: -30 }
                    }
                    submitted={submitted}
                    actionsRef={anchor('actions')}
                    progressRef={anchor('progress')}
                    stagesStart={SUBMITTED + 8}
                    stages={
                        submitted ? workflowStages.map((name, index) => ({ name, status: index === 0 ? 'active' : 'pending' })) : undefined
                    }
                    actions={
                        <Swap
                            at={SUBMITTED}
                            before={
                                <div className="flex flex-col gap-3">
                                    <Button buttonRef={anchor('submit')} variant="sgh-btn-primary w-full" clickAt={SUBMIT_CLICK}>
                                        <IconSendFilled size={16} />
                                        Submit for Approval
                                    </Button>
                                    <span className="sgh-btn sgh-btn-secondary w-full">
                                        <IconPencilFilled size={16} />
                                        Edit Request
                                    </span>
                                    <span className="sgh-btn sgh-btn-danger sgh-btn-outline w-full">
                                        <IconXFilled size={16} />
                                        Cancel Request
                                    </span>
                                </div>
                            }
                            after={
                                <Pop start={SUBMITTED} className="flex flex-col gap-3">
                                    <div className="flex items-center gap-2 rounded-lg bg-primary/10 p-3 text-sm text-primary">
                                        <IconClockFilled size={16} />
                                        Awaiting approval
                                    </div>
                                    <span className="sgh-btn sgh-btn-danger sgh-btn-outline w-full">
                                        <IconXFilled size={16} />
                                        Cancel Request
                                    </span>
                                </Pop>
                            }
                        />
                    }
                />
            </AppShell>
        </Stage>
    );
};
