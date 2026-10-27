import { useCurrentFrame } from 'remotion';
import { IconArrowLeft, IconCircleCheckFilled, IconCircleXFilled, IconEyeFilled } from '@tabler/icons-react';
import { AppShell } from '../components/AppShell';
import { Cursor } from '../components/Cursor';
import { Stage } from '../components/Stage';
import {
    ApprovalProgressCard,
    Button,
    Card,
    FormLabel,
    LineItemsCard,
    Pop,
    RequestDetailsCard,
    Swap,
    Textarea,
    TypeBadge,
} from '../components/ui';
import { advance, people, tenant, workflowStages } from '../data';
import { centerOf, useAnchors } from '../lib/anchors';
import { money, typed, typingEnds } from '../lib/motion';
import { stagesAt } from '../lib/workflow';

export const APPROVE_REQUEST_DURATION = 360;

const REVIEW_CLICK = 52;
const REVIEW_PAGE = REVIEW_CLICK + 8;
const COMMENT_START = 100;
export const APPROVE_CLICK = typingEnds(advance.approvalComment, COMMENT_START, 1.4) + 28;
const APPROVED = APPROVE_CLICK + 6;
const STAGE_APPROVALS = [APPROVED, APPROVED + 44, APPROVED + 84];
export const FULLY_APPROVED = STAGE_APPROVALS[2] + 10;

const TableLabel = ({ children }: { children: string }) => (
    <th>
        <span className="sgh-table-col">
            <span className="sgh-table-col-label">{children}</span>
        </span>
    </th>
);

const Inbox = ({ reviewRef }: { reviewRef: (element: HTMLDivElement | null) => void }) => (
    <>
        <div className="sgh-container-fixed">
            <div className="flex flex-col gap-2 pb-7.5">
                <h1 className="text-xl font-medium leading-none">Approvals Inbox</h1>
                <div className="text-sm text-secondary-foreground">Requests waiting for your approval</div>
            </div>
        </div>
        <div className="sgh-container-fixed">
            <Card
                title="Pending Reviews"
                aside={<span className="sgh-badge sgh-badge-sm sgh-badge-outline">1 item</span>}
                bodyClassName="sgh-card-table"
            >
                <table className="sgh-table sgh-table-border">
                    <thead>
                        <tr>
                            <TableLabel>#</TableLabel>
                            <TableLabel>Staff</TableLabel>
                            <TableLabel>Branch</TableLabel>
                            <TableLabel>Type</TableLabel>
                            <TableLabel>Amount</TableLabel>
                            <TableLabel>Stage</TableLabel>
                            <TableLabel>Submitted</TableLabel>
                            <TableLabel>Actions</TableLabel>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td className="text-sm text-secondary-foreground">#{advance.id}</td>
                            <td className="text-sm font-medium">{people.staff.name}</td>
                            <td className="text-sm">{advance.branch}</td>
                            <td>
                                <TypeBadge />
                            </td>
                            <td className="text-sm font-medium">
                                {advance.currency.symbol} {money(advance.total)}
                            </td>
                            <td className="text-sm">{workflowStages[0]}</td>
                            <td className="text-sm">{advance.submittedOn}</td>
                            <td>
                                <Button buttonRef={reviewRef} variant="sgh-btn-sm sgh-btn-primary" clickAt={REVIEW_CLICK}>
                                    <IconEyeFilled size={14} />
                                    Review
                                </Button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </Card>
        </div>
    </>
);

export const ApproveRequest = () => {
    const frame = useCurrentFrame();
    const { rootRef, anchor, rects } = useAnchors<'review' | 'decision' | 'comment' | 'approve' | 'progress'>();
    const onReviewPage = frame >= REVIEW_PAGE;
    const approved = frame >= APPROVED;
    const commentFocused = frame >= COMMENT_START - 8 && frame < APPROVE_CLICK - 14;

    return (
        <Stage
            url={onReviewPage ? `${tenant.host}/approvals/${advance.id}` : `${tenant.host}/approvals`}
            camera={[
                { frame: 14, zoom: 1.3, focus: centerOf(rects.review, { x: -260, y: 40 }), duration: 26 },
                { frame: REVIEW_PAGE, zoom: 1, duration: 20 },
                { frame: COMMENT_START - 24, zoom: 1.5, focus: centerOf(rects.decision, { x: -80, y: 0 }), duration: 28 },
                { frame: APPROVED + 10, zoom: 1.45, focus: centerOf(rects.progress, { x: -80, y: 0 }), duration: 28 },
            ]}
        >
            <AppShell
                rootRef={rootRef}
                user={people.approver}
                active="approvals"
                approvalsCount={approved ? undefined : 1}
                overlay={
                    <Cursor
                        start={{ x: 820, y: 560 }}
                        moves={[
                            { frame: 20, to: centerOf(rects.review), duration: 26 },
                            { frame: COMMENT_START - 20, to: centerOf(rects.comment, { x: 40, y: 10 }), duration: 20 },
                            { frame: APPROVE_CLICK - 22, to: centerOf(rects.approve, { x: 24, y: 4 }), duration: 18 },
                        ]}
                        clicks={[REVIEW_CLICK, COMMENT_START - 6, APPROVE_CLICK]}
                        hideAfter={APPROVED + 26}
                    />
                }
            >
                <Swap
                    at={REVIEW_PAGE}
                    page
                    before={<Inbox reviewRef={anchor('review')} />}
                    after={
                        <>
                            <div className="sgh-container-fixed">
                                <div className="flex items-end justify-between gap-5 pb-7.5">
                                    <div className="flex flex-col justify-center gap-2">
                                        <div className="flex items-center gap-3">
                                            <h1 className="text-xl font-medium leading-none">Review Request #{advance.id}</h1>
                                            <TypeBadge />
                                        </div>
                                        <div className="flex items-center gap-2 text-sm text-secondary-foreground">
                                            <span>
                                                Stage: <span className="font-medium text-foreground">{workflowStages[0]}</span>
                                            </span>
                                            <span className="sgh-badge sgh-badge-sm sgh-badge-outline">Version 1</span>
                                        </div>
                                    </div>
                                    <div className="flex items-center gap-2.5">
                                        <span className="sgh-btn sgh-btn-outline">
                                            <IconArrowLeft size={16} />
                                            Back to Inbox
                                        </span>
                                        <span className="sgh-btn sgh-btn-outline">
                                            <IconEyeFilled size={16} />
                                            View Request
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div className="sgh-container-fixed">
                                <div className="grid grid-cols-3 gap-7.5">
                                    <div className="col-span-2 flex flex-col gap-7.5">
                                        <RequestDetailsCard submitted />
                                        <LineItemsCard />
                                    </div>
                                    <div className="flex flex-col gap-7.5">
                                        <Card title="Your Decision" cardRef={anchor('decision')} bodyClassName="sgh-card-content p-5">
                                            <Swap
                                                at={APPROVED}
                                                before={
                                                    <div className="flex flex-col gap-4">
                                                        <div>
                                                            <FormLabel>
                                                                Comment{' '}
                                                                <span className="text-xs font-normal text-secondary-foreground">
                                                                    (required for reject / send back)
                                                                </span>
                                                            </FormLabel>
                                                            <Textarea
                                                                textareaRef={anchor('comment')}
                                                                rows={4}
                                                                value={typed(advance.approvalComment, frame, COMMENT_START, 1.4)}
                                                                placeholder="Add a comment…"
                                                                focused={commentFocused}
                                                            />
                                                        </div>
                                                        <div className="flex flex-col gap-2">
                                                            <Button
                                                                buttonRef={anchor('approve')}
                                                                variant="sgh-btn-success w-full"
                                                                clickAt={APPROVE_CLICK}
                                                            >
                                                                <IconCircleCheckFilled size={16} />
                                                                Approve
                                                            </Button>
                                                            <span className="sgh-btn sgh-btn-warning sgh-btn-outline w-full">
                                                                <IconArrowLeft size={16} />
                                                                Send Back
                                                            </span>
                                                            <span className="sgh-btn sgh-btn-danger sgh-btn-outline w-full">
                                                                <IconCircleXFilled size={16} />
                                                                Reject
                                                            </span>
                                                        </div>
                                                    </div>
                                                }
                                                after={
                                                    <Swap
                                                        at={FULLY_APPROVED}
                                                        before={
                                                            <Pop
                                                                start={APPROVED}
                                                                className="flex items-center gap-2 rounded-lg bg-success/10 p-3 text-sm text-success"
                                                            >
                                                                <IconCircleCheckFilled size={16} />
                                                                Stage approved — routed to {workflowStages[1]}
                                                            </Pop>
                                                        }
                                                        after={
                                                            <div className="flex items-center gap-2 rounded-lg bg-success/10 p-3 text-sm text-success">
                                                                <IconCircleCheckFilled size={16} />
                                                                Fully approved — awaiting disbursement
                                                            </div>
                                                        }
                                                    />
                                                }
                                            />
                                        </Card>
                                        <ApprovalProgressCard cardRef={anchor('progress')} stages={stagesAt(frame, STAGE_APPROVALS)} />
                                    </div>
                                </div>
                            </div>
                        </>
                    }
                />
            </AppShell>
        </Stage>
    );
};
