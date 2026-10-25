import { useCurrentFrame } from 'remotion';
import { IconArrowLeft, IconEyeFilled, IconPlusFilled } from '@tabler/icons-react';
import { AppShell } from '../../components/AppShell';
import { Caption } from '../../components/Caption';
import { clickPath, Cursor } from '../../components/Cursor';
import { Stage } from '../../components/Stage';
import { TemplatePage } from '../../components/TemplatePage';
import { Button, Card, FormLabel, Input, Select, Swap, TableLabel, usePop } from '../../components/ui';
import { admin, setupTemplate, tenant } from '../../data';
import { centerOf, useAnchors } from '../../lib/anchors';
import { typed, typingEnds } from '../../lib/motion';

const NEW_CLICK = 34;
const FORM = NEW_CLICK + 8;
const NAME_CLICK = FORM + 24;
const NAME_START = NAME_CLICK + 4;
const TYPE_CLICK = typingEnds(setupTemplate.name, NAME_START, 1.1) + 16;
const OPTION_CLICK = TYPE_CLICK + 24;
const CREATE_CLICK = OPTION_CLICK + 40;
const CREATED = CREATE_CLICK + 6;

export const CREATE_TEMPLATE_DURATION = CREATED + 100;

const templates = [
    { name: 'Expense Approval', type: 'Expense', badge: 'sgh-badge-success', stages: 2 },
    { name: 'Retirement Approval', type: 'Retirement', badge: 'sgh-badge-warning', stages: 2 },
];

const TemplatesIndex = ({ newRef }: { newRef: (element: HTMLDivElement | null) => void }) => (
    <>
        <div className="sgh-container-fixed">
            <div className="flex items-end justify-between gap-5 pb-7.5">
                <div className="flex flex-col justify-center gap-2">
                    <h1 className="text-xl font-medium leading-none">Workflow Templates</h1>
                    <div className="text-sm text-secondary-foreground">Configure approval workflows for requests</div>
                </div>
                <Button buttonRef={newRef} variant="sgh-btn-primary" clickAt={NEW_CLICK}>
                    <IconPlusFilled size={16} />
                    New Template
                </Button>
            </div>
        </div>
        <div className="sgh-container-fixed">
            <Card
                title="All Templates"
                aside={<span className="sgh-badge sgh-badge-sm sgh-badge-outline">{templates.length} templates</span>}
                bodyClassName="sgh-card-table"
            >
                <table className="sgh-table sgh-table-border">
                    <thead>
                        <tr>
                            <TableLabel>Name</TableLabel>
                            <TableLabel>Type</TableLabel>
                            <TableLabel>Branch</TableLabel>
                            <TableLabel>Stages</TableLabel>
                            <TableLabel>Created</TableLabel>
                            <TableLabel>Actions</TableLabel>
                        </tr>
                    </thead>
                    <tbody>
                        {templates.map((template) => (
                            <tr key={template.name}>
                                <td className="text-sm font-medium">{template.name}</td>
                                <td>
                                    <span className={`sgh-badge sgh-badge-sm ${template.badge}`}>{template.type}</span>
                                </td>
                                <td>
                                    <span className="sgh-badge sgh-badge-sm sgh-badge-outline">Master</span>
                                </td>
                                <td>
                                    <span className="sgh-badge sgh-badge-sm sgh-badge-outline">{template.stages}</span>
                                </td>
                                <td className="text-sm">Oct 01, 2026</td>
                                <td>
                                    <span className="sgh-btn sgh-btn-sm sgh-btn-outline">
                                        <IconEyeFilled size={14} />
                                        Configure
                                    </span>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </Card>
        </div>
    </>
);

export const CreateTemplate = () => {
    const frame = useCurrentFrame();
    const { rootRef, anchor, rects } = useAnchors<'new' | 'name' | 'type' | 'option' | 'create' | 'stages'>();
    const dropdownOpen = frame >= TYPE_CLICK + 2 && frame < OPTION_CLICK + 4;
    const dropdownStyle = usePop(TYPE_CLICK + 2);
    const path = clickPath([
        { at: NEW_CLICK, to: centerOf(rects.new) },
        { at: NAME_CLICK, to: centerOf(rects.name, { x: -120, y: 4 }) },
        { at: TYPE_CLICK, to: centerOf(rects.type, { x: -80, y: 4 }) },
        { at: OPTION_CLICK, to: centerOf(rects.option, { x: -100, y: 2 }) },
        { at: CREATE_CLICK, to: centerOf(rects.create, { x: 10, y: 4 }) },
    ]);

    return (
        <Stage
            url={
                frame >= CREATED
                    ? `${tenant.host}/workflow-templates/3`
                    : frame >= FORM
                      ? `${tenant.host}/workflow-templates/create`
                      : `${tenant.host}/workflow-templates`
            }
            camera={[
                { frame: 8, zoom: 1.35, focus: centerOf(rects.new, { x: -320, y: 120 }), duration: 26 },
                { frame: FORM, zoom: 1.4, focus: centerOf(rects.type, { x: -120, y: 60 }), duration: 26 },
                { frame: CREATED + 8, zoom: 1.3, focus: centerOf(rects.stages, { x: 0, y: -60 }), duration: 30 },
            ]}
            overlay={
                <Caption
                    step={4}
                    title="Create the workflow"
                    body="One template per request type. Leave the branch blank to cover every branch."
                />
            }
        >
            <AppShell
                rootRef={rootRef}
                user={admin}
                active="workflows"
                overlay={<Cursor start={{ x: 900, y: 620 }} moves={path.moves} clicks={path.clicks} hideAfter={CREATED + 24} />}
            >
                <Swap
                    at={FORM}
                    page
                    before={<TemplatesIndex newRef={anchor('new')} />}
                    after={
                        <Swap
                            at={CREATED}
                            page
                            before={
                                <>
                                    <div className="sgh-container-fixed">
                                        <div className="flex items-end justify-between gap-5 pb-7.5">
                                            <div className="flex flex-col justify-center gap-2">
                                                <h1 className="text-xl font-medium leading-none">New Workflow Template</h1>
                                                <div className="text-sm text-secondary-foreground">Define a reusable approval workflow</div>
                                            </div>
                                            <span className="sgh-btn sgh-btn-outline">
                                                <IconArrowLeft size={16} />
                                                Back
                                            </span>
                                        </div>
                                    </div>
                                    <div className="sgh-container-fixed">
                                        <Card title="Template Details" bodyClassName="sgh-card-content grid gap-7">
                                            <div className="grid grid-cols-2 gap-5">
                                                <div>
                                                    <FormLabel required>Template Name</FormLabel>
                                                    <Input
                                                        inputRef={anchor('name')}
                                                        value={typed(setupTemplate.name, frame, NAME_START, 1.1)}
                                                        placeholder="e.g. Standard Advance Approval"
                                                        focused={frame >= NAME_CLICK && frame < TYPE_CLICK}
                                                    />
                                                </div>
                                                <div className="relative">
                                                    <FormLabel required>Type</FormLabel>
                                                    <Select
                                                        selectRef={anchor('type')}
                                                        value={frame >= OPTION_CLICK + 2 ? 'Advance' : undefined}
                                                        placeholder="Select type"
                                                        focused={dropdownOpen}
                                                    />
                                                    <div className="mt-1 text-xs text-muted-foreground">
                                                        Advance = payment advance requests • Expense = out-of-pocket reimbursements •
                                                        Retirement = retiring an advance
                                                    </div>
                                                    <div
                                                        className="sgh-dropdown-panel start-0 end-auto top-[66px] z-20 w-full"
                                                        style={{
                                                            opacity: dropdownOpen ? dropdownStyle.opacity : 0,
                                                            transform: dropdownStyle.transform,
                                                        }}
                                                    >
                                                        <div className="sgh-dropdown-item text-muted-foreground">Select type</div>
                                                        <div
                                                            ref={anchor('option')}
                                                            className={`sgh-dropdown-item ${frame >= OPTION_CLICK - 12 ? 'bg-accent text-accent-foreground' : ''}`}
                                                        >
                                                            Advance
                                                        </div>
                                                        <div className="sgh-dropdown-item">Expense</div>
                                                        <div className="sgh-dropdown-item">Retirement</div>
                                                    </div>
                                                </div>
                                                <div>
                                                    <FormLabel>Branch</FormLabel>
                                                    <Select value="Master (all branches)" placeholder="" />
                                                    <div className="mt-1 text-xs text-muted-foreground">
                                                        Leave blank to use this as the default workflow for all branches.
                                                    </div>
                                                </div>
                                            </div>
                                            <div className="mt-2 flex items-center gap-2.5 pt-5">
                                                <Button buttonRef={anchor('create')} variant="sgh-btn-primary" clickAt={CREATE_CLICK}>
                                                    <IconPlusFilled size={16} />
                                                    Create Template
                                                </Button>
                                                <span className="sgh-btn sgh-btn-light">Cancel</span>
                                            </div>
                                        </Card>
                                    </div>
                                </>
                            }
                            after={
                                <TemplatePage
                                    stagesAddedAt={[]}
                                    flash={{ message: 'Workflow template created. Now add stages.', start: CREATED }}
                                    tableRef={anchor('stages')}
                                />
                            }
                        />
                    }
                />
            </AppShell>
        </Stage>
    );
};
