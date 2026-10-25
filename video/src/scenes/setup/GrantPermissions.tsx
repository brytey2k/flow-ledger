import { useCurrentFrame } from 'remotion';
import { IconArrowLeft, IconCheckFilled, IconInfoSquareFilled, IconShieldCheckFilled, IconTrashFilled } from '@tabler/icons-react';
import { AppShell } from '../../components/AppShell';
import { Caption } from '../../components/Caption';
import { clickPath, Cursor } from '../../components/Cursor';
import { Stage } from '../../components/Stage';
import { Button, Card, Checkbox, Flash, FormLabel, Input, PageHeading, Swap } from '../../components/ui';
import { admin, permissionGroups, setupRole, tenant } from '../../data';
import { centerOf, useAnchors } from '../../lib/anchors';
import { tween } from '../../lib/motion';

const MANAGE_CLICK = 36;
const PERMISSIONS_PAGE = MANAGE_CLICK + 8;
const FIRST_TICK = PERMISSIONS_PAGE + 40;
const TICK_GAP = 16;

const grants = permissionGroups.flatMap((group) => group.granted);
const tickAt = (permission: string): number | undefined => {
    const index = grants.indexOf(permission);

    return index === -1 ? undefined : FIRST_TICK + index * TICK_GAP;
};

const LAST_TICK = FIRST_TICK + (grants.length - 1) * TICK_GAP;
const UPDATE_CLICK = LAST_TICK + 44;
const UPDATED = UPDATE_CLICK + 6;

export const GRANT_PERMISSIONS_DURATION = UPDATED + 80;

const EditRole = ({ flashAt, manageRef }: { flashAt?: number; manageRef?: (element: HTMLDivElement | null) => void }) => (
    <>
        {flashAt !== undefined && <Flash message="Role permissions updated successfully." start={flashAt} />}
        <PageHeading title="Edit Role" subtitle="Update role information" back="Back to Roles" />
        <div className="sgh-container-fixed">
            <Card title="Role Details" bodyClassName="sgh-card-content">
                <FormLabel required>Role Name</FormLabel>
                <Input value={setupRole} />
                <p className="mt-1 text-xs text-muted-foreground">Enter a descriptive name for this role.</p>
                <div className="mt-2 flex items-center justify-between gap-2.5 pt-5">
                    <div className="flex items-center gap-2.5">
                        <span className="sgh-btn sgh-btn-primary">
                            <IconCheckFilled size={16} />
                            Update Role
                        </span>
                        <Button buttonRef={manageRef} variant="sgh-btn-outline" clickAt={flashAt === undefined ? MANAGE_CLICK : undefined}>
                            <IconShieldCheckFilled size={16} />
                            Manage Permissions
                        </Button>
                        <span className="sgh-btn sgh-btn-light">Cancel</span>
                    </div>
                    <span className="sgh-btn sgh-btn-danger">
                        <IconTrashFilled size={16} />
                        Delete
                    </span>
                </div>
            </Card>
        </div>
    </>
);

export const GrantPermissions = () => {
    const frame = useCurrentFrame();
    const { rootRef, anchor, rects } = useAnchors<'manage' | 'update' | `group${number}` | `permission${number}`>();
    const scroll = tween(frame, LAST_TICK - 70, 40, 0, 120) + tween(frame, LAST_TICK + 10, 20, 0, 140);
    const path = clickPath(
        [
            { at: MANAGE_CLICK, to: centerOf(rects.manage) },
            ...grants.map((_, index) => ({ at: FIRST_TICK + index * TICK_GAP, to: centerOf(rects[`permission${index}`]) })),
            { at: UPDATE_CLICK, to: centerOf(rects.update, { x: 10, y: 4 }) },
        ],
        12,
    );

    return (
        <Stage
            url={`${tenant.host}/roles/5${frame >= PERMISSIONS_PAGE && frame < UPDATED ? '/permissions' : '/edit'}`}
            camera={[
                { frame: 10, zoom: 1.45, focus: centerOf(rects.manage, { x: 120, y: 30 }), duration: 26 },
                ...permissionGroups.flatMap((group, index) =>
                    group.granted.length === 0
                        ? []
                        : [
                              {
                                  frame: (tickAt(group.granted[0]) as number) - 22,
                                  zoom: 1.4,
                                  focus: centerOf(rects[`group${index}`]),
                                  duration: 22,
                              },
                          ],
                ),
                { frame: UPDATE_CLICK - 30, zoom: 1.3, focus: centerOf(rects.update, { x: 380, y: -120 }), duration: 26 },
                { frame: UPDATED + 10, zoom: 1.2, duration: 30 },
            ]}
            overlay={
                <Caption
                    step={2}
                    title="Grant permissions"
                    body="Pick what the role can do. Everyone holding the role inherits these permissions."
                />
            }
        >
            <AppShell
                rootRef={rootRef}
                user={admin}
                active="roles"
                scroll={frame >= UPDATED ? 0 : scroll}
                overlay={<Cursor start={{ x: 900, y: 620 }} moves={path.moves} clicks={path.clicks} hideAfter={UPDATED + 24} />}
            >
                <Swap
                    at={PERMISSIONS_PAGE}
                    page
                    before={<EditRole manageRef={anchor('manage')} />}
                    after={
                        <Swap
                            at={UPDATED}
                            page
                            before={
                                <>
                                    <div className="sgh-container-fixed">
                                        <div className="flex items-end justify-between gap-5 pb-7.5">
                                            <div className="flex flex-col justify-center gap-2">
                                                <h1 className="text-xl font-medium leading-none">Manage Role Permissions</h1>
                                                <div className="text-sm text-secondary-foreground">
                                                    Assign permissions to {setupRole} role
                                                </div>
                                            </div>
                                            <span className="sgh-btn sgh-btn-outline">
                                                <IconArrowLeft size={16} />
                                                Back to Edit Role
                                            </span>
                                        </div>
                                    </div>
                                    <div className="sgh-container-fixed pb-7.5">
                                        <Card title="Role Permissions" bodyClassName="sgh-card-content grid gap-7">
                                            <div className="rounded-lg bg-muted/50 p-4">
                                                <p className="flex items-center gap-1.5 text-sm text-secondary-foreground">
                                                    <IconInfoSquareFilled size={16} />
                                                    Select the permissions that should be assigned to this role. All users with this role
                                                    will inherit these permissions.
                                                </p>
                                            </div>
                                            <div>
                                                <div className="flex items-center gap-2 border-b border-border pb-4">
                                                    <Checkbox label="Select All Permissions" checked={false} />
                                                </div>
                                                <div className="mt-6 grid gap-6">
                                                    {permissionGroups.map((group, groupIndex) => (
                                                        <div key={group.category} ref={anchor(`group${groupIndex}`)}>
                                                            <h4 className="mb-3 text-sm font-semibold text-secondary-foreground">
                                                                {group.category}
                                                            </h4>
                                                            <div className="grid grid-cols-3 gap-4">
                                                                {group.permissions.map((permission) => {
                                                                    const at = tickAt(permission);

                                                                    return (
                                                                        <Checkbox
                                                                            key={permission}
                                                                            label={permission}
                                                                            checked={at !== undefined && frame >= at}
                                                                            checkedAt={at}
                                                                            checkboxRef={
                                                                                at === undefined
                                                                                    ? undefined
                                                                                    : anchor(`permission${grants.indexOf(permission)}`)
                                                                            }
                                                                        />
                                                                    );
                                                                })}
                                                            </div>
                                                        </div>
                                                    ))}
                                                </div>
                                            </div>
                                            <div className="mt-2 flex items-center gap-2.5 border-t border-border pt-5">
                                                <Button buttonRef={anchor('update')} variant="sgh-btn-primary" clickAt={UPDATE_CLICK}>
                                                    <IconCheckFilled size={16} />
                                                    Update Permissions
                                                </Button>
                                                <span className="sgh-btn sgh-btn-light">Cancel</span>
                                            </div>
                                        </Card>
                                    </div>
                                </>
                            }
                            after={<EditRole flashAt={UPDATED} />}
                        />
                    }
                />
            </AppShell>
        </Stage>
    );
};
