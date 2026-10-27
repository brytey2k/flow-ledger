import type { Ref } from 'react';
import { useCurrentFrame } from 'remotion';
import { IconArrowLeft, IconArrowsExchange, IconClockFilled, IconPencilFilled, IconPlusFilled } from '@tabler/icons-react';
import { setupStages, setupTemplate } from '../data';
import { Button, Card, Flash, Input, Pop, Select, TableLabel } from './ui';

/**
 * Re-creation of workflow-templates/show.blade.php. Each stage appears at
 * its `addedAt` frame; stages without one are not added yet.
 */
export const TemplatePage = ({
    stagesAddedAt,
    flash,
    tableRef,
    addStageRef,
    addStageClickAt,
}: {
    stagesAddedAt: number[];
    flash?: { message: string; start: number };
    tableRef?: Ref<HTMLDivElement>;
    addStageRef?: Ref<HTMLDivElement>;
    addStageClickAt?: number;
}) => {
    const frame = useCurrentFrame();
    const added = setupStages.filter((_, index) => stagesAddedAt[index] !== undefined && frame >= stagesAddedAt[index]);

    return (
        <>
            {flash && <Flash key={flash.message} message={flash.message} start={flash.start} />}
            <div className="sgh-container-fixed">
                <div className="flex items-end justify-between gap-5 pb-7.5">
                    <div className="flex flex-col justify-center gap-2">
                        <h1 className="text-xl font-medium leading-none">{setupTemplate.name}</h1>
                        <div className="flex items-center gap-2 text-sm text-secondary-foreground">
                            <span className="sgh-badge sgh-badge-sm sgh-badge-primary">Advance</span>
                            <span className="sgh-badge sgh-badge-sm sgh-badge-outline">Version 1</span>• {added.length}{' '}
                            {added.length === 1 ? 'stage' : 'stages'}
                        </div>
                    </div>
                    <div className="flex items-center gap-2.5">
                        <span className="sgh-btn sgh-btn-outline">
                            <IconClockFilled size={16} />
                            Version History
                        </span>
                        <span className="sgh-btn sgh-btn-outline">
                            <IconPencilFilled size={16} />
                            Edit Template
                        </span>
                        <span className="sgh-btn sgh-btn-outline">
                            <IconArrowLeft size={16} />
                            Back
                        </span>
                    </div>
                </div>
            </div>
            <div className="sgh-container-fixed pb-7.5">
                <div className="grid gap-7.5">
                    <Card
                        title="Parallel Groups"
                        aside={<div className="text-xs text-muted-foreground">Assign stages to a group to run them simultaneously</div>}
                        bodyClassName="sgh-card-content p-5"
                    >
                        <div className="flex items-start gap-3">
                            <div className="w-64">
                                <label className="sgh-form-label mb-1 block text-xs">Group Name</label>
                                <Input value="" placeholder="e.g. Finance & HR" />
                            </div>
                            <div className="w-64">
                                <label className="sgh-form-label mb-1 block text-xs">Logic</label>
                                <Select value="ALL must approve (AND)" placeholder="" />
                            </div>
                            <span className="sgh-btn sgh-btn-sm sgh-btn-outline mt-5">
                                <IconPlusFilled size={14} />
                                Add Group
                            </span>
                        </div>
                    </Card>

                    <div ref={tableRef} className="sgh-card">
                        <div className="sgh-card-header">
                            <h3 className="sgh-card-title">Approval Stages</h3>
                            <div className="text-xs text-muted-foreground">
                                Stages run in order (lower number first). Same order = parallel.
                            </div>
                        </div>
                        {added.length === 0 ? (
                            <div className="sgh-card-content p-7.5">
                                <div className="flex flex-col items-center justify-center py-8">
                                    <IconArrowsExchange size={48} className="mb-3 text-muted-foreground" />
                                    <p className="mb-4 text-sm text-secondary-foreground">
                                        No stages yet. Add the first approval stage below.
                                    </p>
                                </div>
                            </div>
                        ) : (
                            <div className="sgh-card-table border-b border-border">
                                <table className="sgh-table sgh-table-border">
                                    <thead>
                                        <tr>
                                            <TableLabel>Order</TableLabel>
                                            <TableLabel>Stage Name</TableLabel>
                                            <TableLabel>Roles</TableLabel>
                                            <TableLabel>Parallel Group</TableLabel>
                                            <TableLabel>Skip Below</TableLabel>
                                            <TableLabel>Approver Scope</TableLabel>
                                            <TableLabel>Actions</TableLabel>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {added.map((stage, index) => (
                                            <tr key={stage.name}>
                                                <td>
                                                    <Pop start={stagesAddedAt[index]}>
                                                        <span className="sgh-badge sgh-badge-sm sgh-badge-primary">{index + 1}</span>
                                                    </Pop>
                                                </td>
                                                <td>
                                                    <Pop start={stagesAddedAt[index] + 2}>
                                                        <span className="text-sm font-medium">{stage.name}</span>
                                                    </Pop>
                                                </td>
                                                <td>
                                                    <Pop start={stagesAddedAt[index] + 4}>
                                                        <span className="sgh-badge sgh-badge-sm sgh-badge-outline">{stage.role}</span>
                                                    </Pop>
                                                </td>
                                                <td>
                                                    <span className="text-xs text-muted-foreground">Sequential</span>
                                                </td>
                                                <td>
                                                    <span className="text-xs text-muted-foreground">—</span>
                                                </td>
                                                <td>
                                                    <span className="sgh-badge sgh-badge-sm sgh-badge-info">{stage.scope}</span>
                                                </td>
                                                <td>
                                                    <span className="sgh-btn sgh-btn-sm sgh-btn-outline">
                                                        <IconPencilFilled size={14} />
                                                    </span>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                        <div className="sgh-card-footer p-5">
                            <Button buttonRef={addStageRef} variant="sgh-btn-sm sgh-btn-primary" clickAt={addStageClickAt}>
                                <IconPlusFilled size={14} />
                                Add Stage
                            </Button>
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
};
