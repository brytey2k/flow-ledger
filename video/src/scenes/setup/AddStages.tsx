import { useCurrentFrame } from 'remotion';
import { IconArrowLeft, IconPlusFilled } from '@tabler/icons-react';
import { AppShell } from '../../components/AppShell';
import { clickPath, Cursor } from '../../components/Cursor';
import { Stage } from '../../components/Stage';
import { TemplatePage } from '../../components/TemplatePage';
import { Button, Card, Checkbox, FormLabel, Input, Swap } from '../../components/ui';
import { admin, allRoleNames, setupStages, setupTemplate, tenant } from '../../data';
import { centerOf, useAnchors } from '../../lib/anchors';
import { tween, typed, typingEnds } from '../../lib/motion';

const firstStage = setupStages[0];

const NAME_CLICK = 30;
const NAME_START = NAME_CLICK + 4;
const ROLE_CLICK = typingEnds(firstStage.name, NAME_START, 1.1) + 40;
const ADD_CLICK = ROLE_CLICK + 36;
const ADDED = ADD_CLICK + 6;
const STAGES_ADDED_AT = [ADDED, ADDED + 44, ADDED + 76, ADDED + 108];

export const ADD_STAGES_DURATION = STAGES_ADDED_AT[3] + 90;

export const AddStages = () => {
    const frame = useCurrentFrame();
    const { rootRef, anchor, rects } = useAnchors<'name' | 'role' | 'roles' | 'add' | 'table' | 'details'>();
    const path = clickPath([
        { at: NAME_CLICK, to: centerOf(rects.name, { x: -120, y: 4 }) },
        { at: ROLE_CLICK, to: centerOf(rects.role) },
        { at: ADD_CLICK, to: centerOf(rects.add, { x: 10, y: 4 }) },
    ]);

    return (
        <Stage
            url={frame >= ADDED ? `${tenant.host}/workflow-templates/3` : `${tenant.host}/workflow-templates/3/stages/create`}
            camera={[
                { frame: 8, zoom: 1.4, focus: centerOf(rects.details, { x: 0, y: -170 }), duration: 26 },
                { frame: ROLE_CLICK - 34, zoom: 1.45, focus: centerOf(rects.roles, { x: 0, y: 10 }), duration: 26 },
                { frame: ADD_CLICK - 24, zoom: 1.3, focus: centerOf(rects.add, { x: 420, y: -140 }), duration: 22 },
                { frame: ADDED + 8, zoom: 1.3, focus: centerOf(rects.table, { x: 0, y: 0 }), duration: 30 },
            ]}
        >
            <AppShell
                rootRef={rootRef}
                user={admin}
                active="workflows"
                scroll={frame >= ADDED ? 240 : tween(frame, ROLE_CLICK - 40, 26, 0, 240)}
                overlay={<Cursor start={{ x: 900, y: 620 }} moves={path.moves} clicks={path.clicks} hideAfter={ADDED + 20} />}
            >
                <Swap
                    at={ADDED}
                    page
                    before={
                        <>
                            <div className="sgh-container-fixed">
                                <div className="flex items-end justify-between gap-5 pb-7.5">
                                    <div className="flex flex-col justify-center gap-2">
                                        <h1 className="text-xl font-medium leading-none">Add Stage</h1>
                                        <div className="text-sm text-secondary-foreground">{setupTemplate.name}</div>
                                    </div>
                                    <span className="sgh-btn sgh-btn-outline">
                                        <IconArrowLeft size={16} />
                                        Back
                                    </span>
                                </div>
                            </div>
                            <div className="sgh-container-fixed pb-7.5">
                                <Card title="Stage Details" cardRef={anchor('details')} bodyClassName="sgh-card-content grid gap-7">
                                    <div className="grid grid-cols-2 gap-5">
                                        <div>
                                            <FormLabel required>Stage Name</FormLabel>
                                            <Input
                                                inputRef={anchor('name')}
                                                value={typed(firstStage.name, frame, NAME_START, 1.1)}
                                                placeholder="e.g. Line Manager Approval"
                                                focused={frame >= NAME_CLICK && frame < ROLE_CLICK - 20}
                                            />
                                        </div>
                                        <div>
                                            <FormLabel required>Display Order</FormLabel>
                                            <Input value="1" />
                                            <div className="mt-1 text-xs text-muted-foreground">
                                                Lower numbers run first. Same number = parallel.
                                            </div>
                                        </div>
                                        <div>
                                            <FormLabel>Skip Below Amount</FormLabel>
                                            <Input value="" placeholder="e.g. 500.00" />
                                            <div className="mt-1 text-xs text-muted-foreground">
                                                Auto-skip this stage when request total is below this amount. Leave empty to never skip.
                                            </div>
                                        </div>
                                    </div>
                                    <div className="grid grid-cols-2 gap-4">
                                        <Checkbox
                                            className="sgh-checkbox"
                                            label="Restrict approvers to submitter's department"
                                            checked={false}
                                        />
                                        <Checkbox className="sgh-checkbox" label="Restrict approvers to request's branch" checked />
                                        <Checkbox className="sgh-checkbox" label="Allow send-back at this stage" checked />
                                        <Checkbox className="sgh-checkbox" label="Allow attachment-only document requests" checked />
                                    </div>
                                    <div ref={anchor('roles')}>
                                        <FormLabel required>Roles that can approve this stage</FormLabel>
                                        <div className="mb-3 mt-1 text-xs text-muted-foreground">
                                            Any user with one of these roles will see this stage in their approvals inbox.
                                        </div>
                                        <div className="grid grid-cols-4 gap-3">
                                            {allRoleNames.map((role) => (
                                                <Checkbox
                                                    key={role}
                                                    className="sgh-checkbox"
                                                    label={role}
                                                    checked={role === firstStage.role && frame >= ROLE_CLICK}
                                                    checkedAt={role === firstStage.role ? ROLE_CLICK : undefined}
                                                    checkboxRef={role === firstStage.role ? anchor('role') : undefined}
                                                />
                                            ))}
                                        </div>
                                    </div>
                                    <div className="rounded-lg border border-border p-4">
                                        <FormLabel>Fallback approver roles</FormLabel>
                                        <div className="mb-3 text-xs text-muted-foreground">
                                            Used only when every primary approver is excluded. Department and branch restrictions still
                                            apply.
                                        </div>
                                        <div className="grid grid-cols-4 gap-3">
                                            {allRoleNames.map((role) => (
                                                <Checkbox key={role} className="sgh-checkbox" label={role} checked={false} />
                                            ))}
                                        </div>
                                    </div>
                                    <div className="flex items-center gap-2.5">
                                        <Button buttonRef={anchor('add')} variant="sgh-btn-primary" clickAt={ADD_CLICK}>
                                            <IconPlusFilled size={16} />
                                            Add Stage
                                        </Button>
                                        <span className="sgh-btn sgh-btn-light">Cancel</span>
                                    </div>
                                </Card>
                            </div>
                        </>
                    }
                    after={
                        <TemplatePage
                            stagesAddedAt={STAGES_ADDED_AT}
                            flash={{ message: 'Stage added.', start: ADDED }}
                            tableRef={anchor('table')}
                        />
                    }
                />
            </AppShell>
        </Stage>
    );
};
