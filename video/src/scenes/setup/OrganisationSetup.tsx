import type { ReactNode } from 'react';
import { useCurrentFrame } from 'remotion';
import { IconEditFilled, IconPlusFilled, IconTrashFilled, IconUpload } from '@tabler/icons-react';
import { AppShell, type NavKey } from '../../components/AppShell';
import { clickPath, Cursor } from '../../components/Cursor';
import { Stage } from '../../components/Stage';
import { Card, PageSequence, Pop, TableLabel } from '../../components/ui';
import { admin, branches, costCodes, currencies, departments, tenant } from '../../data';
import { centerOf, useAnchors } from '../../lib/anchors';

const FIRST_PAGE = 0;
const PAGE_FRAMES = 66;
const pageKeys: NavKey[] = ['branches', 'departments', 'cost-codes', 'currencies'];
const pageAt = (index: number): number => FIRST_PAGE + index * PAGE_FRAMES;
const urls = ['branches', 'departments', 'cost-codes', 'currencies'];

export const ORGANISATION_SETUP_DURATION = pageAt(pageKeys.length) + 10;

const Actions = () => (
    <div className="flex items-center justify-center gap-2">
        <span className="sgh-btn sgh-btn-sm sgh-btn-icon sgh-btn-ghost text-primary">
            <IconEditFilled size={18} />
        </span>
        <span className="sgh-btn sgh-btn-sm sgh-btn-icon sgh-btn-ghost text-danger">
            <IconTrashFilled size={18} />
        </span>
    </div>
);

/**
 * An organisation index page: heading, add/import buttons and a table whose
 * rows pop in one after another.
 */
const IndexPage = ({
    at,
    title,
    subtitle,
    add,
    importLabel,
    cardTitle,
    count,
    legacyCountBadge = false,
    columns,
    rows,
    tableRef,
}: {
    at: number;
    title: string;
    subtitle: string;
    add: string;
    importLabel?: string;
    cardTitle: string;
    count: string;
    legacyCountBadge?: boolean;
    columns: string[];
    rows: ReactNode[][];
    tableRef: (element: HTMLDivElement | null) => void;
}) => (
    <>
        <div className="sgh-container-fixed">
            <div className="flex items-end justify-between gap-5 pb-7.5">
                <div className="flex flex-col justify-center gap-2">
                    <h1 className="text-xl font-medium leading-none">{title}</h1>
                    <div className="text-sm text-secondary-foreground">{subtitle}</div>
                </div>
                <div className="flex items-center gap-2.5">
                    {importLabel && (
                        <span className="sgh-btn sgh-btn-outline">
                            <IconUpload size={16} />
                            {importLabel}
                        </span>
                    )}
                    <span className="sgh-btn sgh-btn-primary">
                        <IconPlusFilled size={16} />
                        {add}
                    </span>
                </div>
            </div>
        </div>
        <div className="sgh-container-fixed">
            <Card
                title={cardTitle}
                cardRef={tableRef}
                aside={
                    legacyCountBadge ? (
                        <span className="text-sm">{count}</span>
                    ) : (
                        <span className="sgh-badge sgh-badge-sm sgh-badge-outline">{count}</span>
                    )
                }
                bodyClassName="sgh-card-table"
            >
                <table className="sgh-table sgh-table-border">
                    <thead>
                        <tr>
                            {columns.map((column) => (
                                <TableLabel key={column}>{column}</TableLabel>
                            ))}
                        </tr>
                    </thead>
                    <tbody>
                        {rows.map((cells, rowIndex) => (
                            <tr key={rowIndex}>
                                {cells.map((cell, cellIndex) => (
                                    <td key={cellIndex}>
                                        <Pop start={at + 6 + rowIndex * 4}>{cell}</Pop>
                                    </td>
                                ))}
                            </tr>
                        ))}
                    </tbody>
                </table>
            </Card>
        </div>
    </>
);

const text = (value: string, className = 'text-sm') => <span className={className}>{value}</span>;

export const OrganisationSetup = () => {
    const frame = useCurrentFrame();
    const { rootRef, anchor, rects } = useAnchors<NavKey | `table${number}`>();
    const current = pageKeys.reduce((active, _, index) => (frame >= pageAt(index) ? index : active), 0);
    const path = clickPath(
        pageKeys.slice(1).map((key, index) => ({ at: pageAt(index + 1) - 2, to: centerOf(rects[key], { x: -40, y: 2 }) })),
        16,
    );

    return (
        <Stage
            url={`${tenant.host}/${urls[current]}`}
            camera={[
                { frame: 0, zoom: 1.12, focus: centerOf(rects.table0, { x: -40, y: -40 }), duration: 1 },
                ...pageKeys.slice(1).map((_, index) => ({
                    frame: pageAt(index + 1) - 6,
                    zoom: 1.12,
                    focus: centerOf(rects[`table${index + 1}`], { x: -40, y: -40 }),
                    duration: 18,
                })),
            ]}
        >
            <AppShell
                rootRef={rootRef}
                user={admin}
                active={pageKeys[current]}
                navAnchor={(key) => anchor(key)}
                overlay={<Cursor start={{ x: 520, y: 700 }} moves={path.moves} clicks={path.clicks} />}
            >
                <PageSequence
                    pages={[
                        {
                            at: pageAt(0),
                            content: (
                                <IndexPage
                                    at={pageAt(0)}
                                    title="Branches Management"
                                    subtitle="Manage organisational branches and hierarchy"
                                    add="Add New Branch"
                                    cardTitle="All Branches"
                                    count={`${branches.length} Branches`}
                                    columns={['Branch Name', 'Code', 'Level', 'Parent', 'Actions']}
                                    rows={branches.map((branch) => [
                                        text(branch.name, 'text-sm font-medium'),
                                        <span className="sgh-badge sgh-badge-sm sgh-badge-outline">{branch.code}</span>,
                                        text(branch.level),
                                        text(branch.parent, 'text-sm text-secondary-foreground'),
                                        <Actions />,
                                    ])}
                                    tableRef={anchor('table0')}
                                />
                            ),
                        },
                        {
                            at: pageAt(1),
                            content: (
                                <IndexPage
                                    at={pageAt(1)}
                                    title="Departments Management"
                                    subtitle="Manage organisational departments"
                                    add="Add New Department"
                                    importLabel="Import Departments"
                                    cardTitle="All Departments"
                                    count={`${departments.length} Departments`}
                                    columns={['ID', 'Department Name', 'Created', 'Actions']}
                                    rows={departments.map((department, index) => [
                                        <span className="sgh-badge sgh-badge-sm sgh-badge-primary">{index + 1}</span>,
                                        text(department, 'text-sm font-medium'),
                                        text('Oct 01, 2026'),
                                        <Actions />,
                                    ])}
                                    tableRef={anchor('table1')}
                                />
                            ),
                        },
                        {
                            at: pageAt(2),
                            content: (
                                <IndexPage
                                    at={pageAt(2)}
                                    title="Cost Codes"
                                    subtitle="Manage cost codes and their department assignments"
                                    add="Add New Cost Code"
                                    importLabel="Import Cost Codes"
                                    cardTitle="All Cost Codes"
                                    count={`${costCodes.length} Cost Codes`}
                                    columns={['ID', 'Code', 'Name', 'Department', 'Actions']}
                                    rows={costCodes.map((costCode, index) => [
                                        <span className="sgh-badge sgh-badge-sm sgh-badge-primary">{index + 1}</span>,
                                        text(costCode.code, 'text-sm font-medium'),
                                        text(costCode.name),
                                        text(costCode.department, 'text-sm text-secondary-foreground'),
                                        <Actions />,
                                    ])}
                                    tableRef={anchor('table2')}
                                />
                            ),
                        },
                        {
                            at: pageAt(3),
                            content: (
                                <IndexPage
                                    at={pageAt(3)}
                                    title="Currencies Management"
                                    subtitle="Manage currencies used across the system"
                                    add="Add New Currency"
                                    cardTitle="All Currencies"
                                    count={`${currencies.length} Currencies`}
                                    legacyCountBadge
                                    columns={['Name', 'Code', 'Symbol', 'Created', 'Actions']}
                                    rows={currencies.map((currency) => [
                                        text(currency.name, 'text-sm font-medium'),
                                        <span className="sgh-badge sgh-badge-sm sgh-badge-outline">{currency.code}</span>,
                                        text(currency.symbol),
                                        text('Oct 01, 2026'),
                                        <span className="sgh-btn sgh-btn-sm sgh-btn-outline">Denominations</span>,
                                    ])}
                                    tableRef={anchor('table3')}
                                />
                            ),
                        },
                    ]}
                />
            </AppShell>
        </Stage>
    );
};
