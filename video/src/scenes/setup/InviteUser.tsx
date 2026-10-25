import { useCurrentFrame } from 'remotion';
import { IconArrowLeft, IconMessageCircleFilled, IconPlusFilled } from '@tabler/icons-react';
import { AppShell } from '../../components/AppShell';
import { Caption } from '../../components/Caption';
import { clickPath, Cursor } from '../../components/Cursor';
import { Stage } from '../../components/Stage';
import { Button, Card, Checkbox, Flash, FormLabel, Input, Select, Swap, TableLabel } from '../../components/ui';
import { admin, allRoleNames, invitee, setupRole, tenant } from '../../data';
import { centerOf, useAnchors } from '../../lib/anchors';
import { enter, tween, typed, typingEnds } from '../../lib/motion';

const SPEED = 1.3;
const FIRST_CLICK = 30;
const FIRST_START = FIRST_CLICK + 4;
const LAST_CLICK = typingEnds(invitee.firstName, FIRST_START, SPEED) + 12;
const LAST_START = LAST_CLICK + 4;
const EMAIL_CLICK = typingEnds(invitee.lastName, LAST_START, SPEED) + 12;
const EMAIL_START = EMAIL_CLICK + 4;
const BRANCH_CLICK = typingEnds(invitee.email, EMAIL_START, SPEED) + 14;
const BRANCH_CHOSEN = BRANCH_CLICK + 10;
const ROLE_CLICK = BRANCH_CHOSEN + 26;
const INVITE_CLICK = ROLE_CLICK + 34;
const INVITED = INVITE_CLICK + 6;

export const INVITE_USER_DURATION = INVITED + 90;

const users = [
    { name: 'Nana Adjei', email: 'nana.adjei@acmelogistics.com', roles: ['Admin'], status: 'Active' },
    { name: 'Kofi Asare', email: 'kofi.asare@acmelogistics.com', roles: ['Finance Director'], status: 'Active' },
    { name: 'Abena Osei', email: 'abena.osei@acmelogistics.com', roles: ['Finance Manager'], status: 'Active' },
    { name: 'Kwame Boateng', email: 'kwame.boateng@acmelogistics.com', roles: ['Finance Officer'], status: 'Active' },
];

const UsersIndex = ({ rowRef }: { rowRef: (element: HTMLTableRowElement | null) => void }) => {
    const frame = useCurrentFrame();
    const highlight = enter(frame, INVITED) * tween(frame, INVITED + 40, 40, 1, 0.35);
    const rows = [
        { name: `${invitee.firstName} ${invitee.lastName}`, email: invitee.email, roles: [setupRole], status: 'Invited' },
        ...users,
    ];

    return (
        <>
            <Flash message="Invitation sent successfully." start={INVITED} />
            <div className="sgh-container-fixed">
                <div className="flex items-end justify-between gap-5 pb-7.5">
                    <div className="flex flex-col justify-center gap-2">
                        <h1 className="text-xl font-medium leading-none">Users Management</h1>
                        <div className="text-sm text-secondary-foreground">Manage users and their roles</div>
                    </div>
                    <span className="sgh-btn sgh-btn-primary">
                        <IconPlusFilled size={16} />
                        Add New User
                    </span>
                </div>
            </div>
            <div className="sgh-container-fixed">
                <Card title="All Users" aside={<span className="text-sm">{rows.length} Users</span>} bodyClassName="sgh-card-table">
                    <table className="sgh-table sgh-table-border">
                        <thead>
                            <tr>
                                <TableLabel>Name</TableLabel>
                                <TableLabel>Email</TableLabel>
                                <TableLabel>Roles</TableLabel>
                                <TableLabel>Status</TableLabel>
                            </tr>
                        </thead>
                        <tbody>
                            {rows.map((user, index) => (
                                <tr
                                    key={user.email}
                                    ref={index === 0 ? rowRef : undefined}
                                    style={
                                        index === 0
                                            ? { backgroundColor: `color-mix(in oklch, var(--accent) ${highlight * 100}%, transparent)` }
                                            : undefined
                                    }
                                >
                                    <td className="text-sm font-medium">{user.name}</td>
                                    <td className="text-sm text-secondary-foreground">{user.email}</td>
                                    <td>
                                        <div className="flex flex-wrap gap-1">
                                            {user.roles.map((role) => (
                                                <span key={role} className="sgh-badge sgh-badge-sm sgh-badge-primary">
                                                    {role}
                                                </span>
                                            ))}
                                        </div>
                                    </td>
                                    <td className="text-sm">{user.status}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </Card>
            </div>
        </>
    );
};

export const InviteUser = () => {
    const frame = useCurrentFrame();
    const { rootRef, anchor, rects } = useAnchors<'first' | 'last' | 'email' | 'branch' | 'role' | 'invite' | 'row' | 'details'>();
    const focused = (from: number, until: number): boolean => frame >= from && frame < until;
    const path = clickPath([
        { at: FIRST_CLICK, to: centerOf(rects.first, { x: -100, y: 4 }) },
        { at: LAST_CLICK, to: centerOf(rects.last, { x: -100, y: 4 }) },
        { at: EMAIL_CLICK, to: centerOf(rects.email, { x: -100, y: 4 }) },
        { at: BRANCH_CLICK, to: centerOf(rects.branch, { x: -60, y: 4 }) },
        { at: ROLE_CLICK, to: centerOf(rects.role) },
        { at: INVITE_CLICK, to: centerOf(rects.invite, { x: 10, y: 4 }) },
    ]);

    return (
        <Stage
            url={frame >= INVITED ? `${tenant.host}/users` : `${tenant.host}/users/invite`}
            camera={[
                { frame: 8, zoom: 1.4, focus: centerOf(rects.details, { x: 0, y: -120 }), duration: 26 },
                { frame: ROLE_CLICK - 30, zoom: 1.4, focus: centerOf(rects.role, { x: 260, y: 20 }), duration: 26 },
                { frame: INVITED + 6, zoom: 1.35, focus: centerOf(rects.row, { x: 60, y: 60 }), duration: 30 },
            ]}
            overlay={
                <Caption
                    step={3}
                    title="Invite people into roles"
                    body="Give each person the roles they hold. Their approvals inbox follows from it."
                />
            }
        >
            <AppShell
                rootRef={rootRef}
                user={admin}
                active="users"
                overlay={<Cursor start={{ x: 900, y: 620 }} moves={path.moves} clicks={path.clicks} hideAfter={INVITED + 24} />}
            >
                <Swap
                    at={INVITED}
                    page
                    before={
                        <>
                            <div className="sgh-container-fixed">
                                <div className="flex items-end justify-between gap-5 pb-7.5">
                                    <div className="flex flex-col justify-center gap-2">
                                        <h1 className="text-xl font-medium leading-none">Invite User</h1>
                                        <div className="text-sm text-secondary-foreground">Send an invitation to a new user</div>
                                    </div>
                                    <span className="sgh-btn sgh-btn-outline">
                                        <IconArrowLeft size={16} />
                                        Back to Users
                                    </span>
                                </div>
                            </div>
                            <div className="sgh-container-fixed">
                                <Card title="User Details" cardRef={anchor('details')} bodyClassName="sgh-card-content grid gap-7">
                                    <div className="grid grid-cols-2 gap-5">
                                        <div>
                                            <FormLabel required>First Name</FormLabel>
                                            <Input
                                                inputRef={anchor('first')}
                                                value={typed(invitee.firstName, frame, FIRST_START, SPEED)}
                                                placeholder="e.g. John"
                                                focused={focused(FIRST_CLICK, LAST_CLICK)}
                                            />
                                        </div>
                                        <div>
                                            <FormLabel required>Last Name</FormLabel>
                                            <Input
                                                inputRef={anchor('last')}
                                                value={typed(invitee.lastName, frame, LAST_START, SPEED)}
                                                placeholder="e.g. Doe"
                                                focused={focused(LAST_CLICK, EMAIL_CLICK)}
                                            />
                                        </div>
                                        <div className="col-span-2">
                                            <FormLabel required>Email</FormLabel>
                                            <Input
                                                inputRef={anchor('email')}
                                                value={typed(invitee.email, frame, EMAIL_START, SPEED)}
                                                placeholder="e.g. john@example.com"
                                                focused={focused(EMAIL_CLICK, BRANCH_CLICK)}
                                            />
                                        </div>
                                        <div>
                                            <FormLabel required>Branch</FormLabel>
                                            <Select
                                                selectRef={anchor('branch')}
                                                value={frame >= BRANCH_CHOSEN ? invitee.branch : undefined}
                                                placeholder="Select a branch"
                                                focused={focused(BRANCH_CLICK, ROLE_CLICK - 10)}
                                            />
                                        </div>
                                        <div>
                                            <FormLabel>Operational Branch</FormLabel>
                                            <Select placeholder="Same as branch" />
                                        </div>
                                        <div className="col-span-2">
                                            <FormLabel>Roles</FormLabel>
                                            <div className="grid grid-cols-3 gap-3">
                                                {allRoleNames.map((role) => (
                                                    <Checkbox
                                                        key={role}
                                                        label={role}
                                                        checked={role === setupRole && frame >= ROLE_CLICK}
                                                        checkedAt={role === setupRole ? ROLE_CLICK : undefined}
                                                        checkboxRef={role === setupRole ? anchor('role') : undefined}
                                                    />
                                                ))}
                                            </div>
                                        </div>
                                    </div>
                                    <div className="text-sm text-muted-foreground">
                                        The user will receive an email invitation to set up their account and sign in.
                                    </div>
                                    <div className="flex items-center gap-2.5">
                                        <Button buttonRef={anchor('invite')} variant="sgh-btn-primary" clickAt={INVITE_CLICK}>
                                            <IconMessageCircleFilled size={16} />
                                            Send Invite
                                        </Button>
                                        <span className="sgh-btn sgh-btn-light">Cancel</span>
                                    </div>
                                </Card>
                            </div>
                        </>
                    }
                    after={<UsersIndex rowRef={anchor('row')} />}
                />
            </AppShell>
        </Stage>
    );
};
