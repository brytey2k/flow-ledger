import { useCurrentFrame } from 'remotion';
import { IconArrowLeft, IconEditFilled, IconPlusFilled, IconTrashFilled } from '@tabler/icons-react';
import { AppShell } from '../../components/AppShell';
import { clickPath, Cursor } from '../../components/Cursor';
import { Stage } from '../../components/Stage';
import { Button, Card, Flash, FormLabel, Input, Swap, TableLabel } from '../../components/ui';
import { admin, existingRoles, setupRole, tenant } from '../../data';
import { centerOf, useAnchors } from '../../lib/anchors';
import { enter, tween, typed, typingEnds } from '../../lib/motion';

const ADD_CLICK = 34;
const FORM = ADD_CLICK + 8;
const NAME_CLICK = FORM + 24;
const NAME_START = NAME_CLICK + 4;
const CREATE_CLICK = typingEnds(setupRole, NAME_START, 1.1) + 24;
const CREATED = CREATE_CLICK + 6;

export const CREATE_ROLE_DURATION = CREATED + 90;

type Role = { name: string; users: number; permissions: number; created: string; time: string };

const RoleRow = ({
    role,
    highlightFrom,
    rowRef,
}: {
    role: Role;
    highlightFrom?: number;
    rowRef?: (element: HTMLTableRowElement | null) => void;
}) => {
    const frame = useCurrentFrame();
    const highlight = highlightFrom === undefined ? 0 : enter(frame, highlightFrom) * tween(frame, highlightFrom + 40, 40, 1, 0.35);

    return (
        <tr ref={rowRef} style={{ backgroundColor: `color-mix(in oklch, var(--accent) ${highlight * 100}%, transparent)` }}>
            <td>
                <span className="text-sm font-medium leading-none">{role.name}</span>
            </td>
            <td className="text-sm">
                {role.users} {role.users === 1 ? 'user' : 'users'}
            </td>
            <td className="text-sm">
                {role.permissions} {role.permissions === 1 ? 'permission' : 'permissions'}
            </td>
            <td>
                <div className="flex flex-col gap-0.5">
                    <span className="text-sm">{role.created}</span>
                    <span className="text-xs text-secondary-foreground">{role.time}</span>
                </div>
            </td>
            <td className="text-center">
                <div className="flex items-center justify-center gap-2">
                    <span className="sgh-btn sgh-btn-sm sgh-btn-icon sgh-btn-ghost text-primary">
                        <IconEditFilled size={18} />
                    </span>
                    <span className="sgh-btn sgh-btn-sm sgh-btn-icon sgh-btn-ghost text-danger">
                        <IconTrashFilled size={18} />
                    </span>
                </div>
            </td>
        </tr>
    );
};

/**
 * roles/index.blade.php, optionally with the newly created role inserted.
 */
const RolesIndex = ({
    created,
    addRef,
    newRowRef,
}: {
    created: boolean;
    addRef?: (element: HTMLDivElement | null) => void;
    newRowRef?: (element: HTMLTableRowElement | null) => void;
}) => {
    const roles = created
        ? [
              existingRoles[0],
              { name: setupRole, users: 0, permissions: 0, created: 'Oct 25, 2026', time: '9:41 AM' },
              ...existingRoles.slice(1),
          ]
        : existingRoles;

    return (
        <>
            {created && <Flash message="Role created successfully." start={CREATED} />}
            <div className="sgh-container-fixed">
                <div className="flex items-end justify-between gap-5 pb-7.5">
                    <div className="flex flex-col justify-center gap-2">
                        <h1 className="text-xl font-medium leading-none">Roles Management</h1>
                        <div className="text-sm text-secondary-foreground">Manage roles and their permissions</div>
                    </div>
                    <Button buttonRef={addRef} variant="sgh-btn-primary" clickAt={created ? undefined : ADD_CLICK}>
                        <IconPlusFilled size={16} />
                        Add New Role
                    </Button>
                </div>
            </div>
            <div className="sgh-container-fixed">
                <Card title="All Roles" aside={<span className="text-sm">{roles.length} Roles</span>} bodyClassName="sgh-card-table">
                    <table className="sgh-table sgh-table-border">
                        <thead>
                            <tr>
                                <TableLabel>Role Name</TableLabel>
                                <TableLabel>Users</TableLabel>
                                <TableLabel>Permissions</TableLabel>
                                <TableLabel>Created</TableLabel>
                                <TableLabel>Actions</TableLabel>
                            </tr>
                        </thead>
                        <tbody>
                            {roles.map((role) =>
                                role.name === setupRole ? (
                                    <RoleRow key={role.name} role={role} highlightFrom={CREATED} rowRef={newRowRef} />
                                ) : (
                                    <RoleRow key={role.name} role={role} />
                                ),
                            )}
                        </tbody>
                    </table>
                </Card>
            </div>
        </>
    );
};

export const CreateRole = () => {
    const frame = useCurrentFrame();
    const { rootRef, anchor, rects } = useAnchors<'add' | 'name' | 'create' | 'newRow'>();
    const path = clickPath([
        { at: ADD_CLICK, to: centerOf(rects.add) },
        { at: NAME_CLICK, to: centerOf(rects.name, { x: -120, y: 4 }) },
        { at: CREATE_CLICK, to: centerOf(rects.create, { x: 10, y: 4 }) },
    ]);

    return (
        <Stage
            url={frame >= CREATED ? `${tenant.host}/roles` : frame >= FORM ? `${tenant.host}/roles/create` : `${tenant.host}/roles`}
            camera={[
                { frame: 8, zoom: 1.4, focus: centerOf(rects.add, { x: -260, y: 80 }), duration: 26 },
                { frame: FORM, zoom: 1.45, focus: centerOf(rects.name, { x: 260, y: 60 }), duration: 26 },
                { frame: CREATED + 6, zoom: 1.35, focus: centerOf(rects.newRow, { x: 0, y: 40 }), duration: 30 },
            ]}
        >
            <AppShell
                rootRef={rootRef}
                user={admin}
                active="roles"
                overlay={<Cursor start={{ x: 900, y: 600 }} moves={path.moves} clicks={path.clicks} hideAfter={CREATED + 30} />}
            >
                <Swap
                    at={FORM}
                    page
                    before={<RolesIndex created={false} addRef={anchor('add')} />}
                    after={
                        <Swap
                            at={CREATED}
                            page
                            before={
                                <>
                                    <div className="sgh-container-fixed">
                                        <div className="flex items-end justify-between gap-5 pb-7.5">
                                            <div className="flex flex-col justify-center gap-2">
                                                <h1 className="text-xl font-medium leading-none">Create Role</h1>
                                                <div className="text-sm text-secondary-foreground">Add a new role to your system</div>
                                            </div>
                                            <span className="sgh-btn sgh-btn-outline">
                                                <IconArrowLeft size={16} />
                                                Back to Roles
                                            </span>
                                        </div>
                                    </div>
                                    <div className="sgh-container-fixed">
                                        <Card title="Role Details" bodyClassName="sgh-card-content">
                                            <div className="grid gap-7">
                                                <div className="grid grid-cols-2 gap-5">
                                                    <div>
                                                        <FormLabel required>Role Name</FormLabel>
                                                        <Input
                                                            inputRef={anchor('name')}
                                                            value={typed(setupRole, frame, NAME_START, 1.1)}
                                                            placeholder="e.g. Administrator"
                                                            focused={frame >= NAME_CLICK && frame < CREATE_CLICK - 14}
                                                        />
                                                        <p className="mt-1 text-xs text-muted-foreground">
                                                            Enter a descriptive name for this role.
                                                        </p>
                                                    </div>
                                                    <div>
                                                        <FormLabel>Guard Name</FormLabel>
                                                        <Input value="web" />
                                                        <p className="mt-1 text-xs text-muted-foreground">
                                                            The authentication guard for this role (default: web).
                                                        </p>
                                                    </div>
                                                </div>
                                                <div className="mt-2 flex items-center gap-2.5 pt-5">
                                                    <Button buttonRef={anchor('create')} variant="sgh-btn-primary" clickAt={CREATE_CLICK}>
                                                        <IconPlusFilled size={16} />
                                                        Create Role
                                                    </Button>
                                                    <span className="sgh-btn sgh-btn-light">Cancel</span>
                                                </div>
                                            </div>
                                        </Card>
                                    </div>
                                </>
                            }
                            after={<RolesIndex created newRowRef={anchor('newRow')} />}
                        />
                    }
                />
            </AppShell>
        </Stage>
    );
};
