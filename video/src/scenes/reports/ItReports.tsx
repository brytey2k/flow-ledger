import { useCurrentFrame } from 'remotion';
import { AppShell } from '../../components/AppShell';
import { ReportBody, ReportFilters, ReportHeader } from '../../components/ReportPage';
import { Stage } from '../../components/Stage';
import { Card, PageSequence, Pop, TableLabel } from '../../components/ui';
import { admin, auditActions, sla, slaRows, tenant } from '../../data';
import { centerOf, useAnchors } from '../../lib/anchors';
import { countUp, easeOut, tween } from '../../lib/motion';

const SLA_PAGE = 0;
export const AUDIT_PAGE = 110;

export const IT_REPORTS_DURATION = AUDIT_PAGE + 110;

const actionLabels: Record<string, { label: string; badge: string }> = {
    approve: { label: 'Approved', badge: 'sgh-badge-success' },
    send_back: { label: 'Sent Back', badge: 'sgh-badge-warning' },
    reject: { label: 'Rejected', badge: 'sgh-badge-danger' },
};

const WorkflowSla = ({ frame, kpisRef }: { frame: number; kpisRef: (element: HTMLDivElement | null) => void }) => {
    const average = tween(frame, SLA_PAGE + 10, 34, 0, sla.averageDays, easeOut);

    return (
        <>
            <ReportHeader
                title="Workflow SLA"
                description="Percentage of requests approved within a configurable target number of days (default: 3 days). A compliance KPI management can act on to hold approvers accountable and set service level expectations."
            />
            <ReportBody>
                <ReportFilters
                    filters={[
                        { label: 'From', value: 'Oct 01, 2026' },
                        { label: 'To', value: 'Oct 31, 2026' },
                        { label: 'SLA Target (days)', value: String(sla.slaDays) },
                        { label: 'Type', value: 'All' },
                    ]}
                />
                <div ref={kpisRef} className="grid grid-cols-3 gap-4">
                    <Pop start={SLA_PAGE + 6} className="sgh-card p-5">
                        <div className="mb-1 text-xs text-secondary-foreground">SLA Compliance Rate</div>
                        <div className="text-3xl font-bold text-success">{countUp(frame, SLA_PAGE + 10, 34, 0, sla.complianceRate)}%</div>
                        <div className="mt-1 text-xs text-secondary-foreground">within {sla.slaDays}-day target</div>
                    </Pop>
                    <Pop start={SLA_PAGE + 11} className="sgh-card p-5">
                        <div className="mb-1 text-xs text-secondary-foreground">Average Approval Time</div>
                        <div className="text-3xl font-bold">{average.toFixed(1)}d</div>
                        <div className="mt-1 text-xs text-secondary-foreground">from submission to approval</div>
                    </Pop>
                    <Pop start={SLA_PAGE + 16} className="sgh-card p-5">
                        <div className="mb-1 text-xs text-secondary-foreground">Total Requests</div>
                        <div className="text-3xl font-bold">{countUp(frame, SLA_PAGE + 10, 34, 0, sla.total)}</div>
                        <div className="mt-1 text-xs text-secondary-foreground">
                            {sla.compliant} compliant · {sla.total - sla.compliant} over target
                        </div>
                    </Pop>
                </div>
                <Card title="Request Detail" bodyClassName="sgh-card-table">
                    <table className="sgh-table sgh-table-border">
                        <thead>
                            <tr>
                                {['#', 'Staff', 'Type', 'Submitted', 'Approved', 'Days Taken', 'SLA'].map((column) => (
                                    <TableLabel key={column}>{column}</TableLabel>
                                ))}
                            </tr>
                        </thead>
                        <tbody>
                            {slaRows.map((row) => (
                                <tr key={row.id}>
                                    <td className="text-sm text-secondary-foreground">#{row.id}</td>
                                    <td className="text-sm font-medium">{row.staff}</td>
                                    <td>
                                        <span
                                            className={`sgh-badge sgh-badge-sm ${row.type === 'Advance' ? 'sgh-badge-primary' : 'sgh-badge-warning'}`}
                                        >
                                            {row.type}
                                        </span>
                                    </td>
                                    <td className="text-sm">{row.submitted}</td>
                                    <td className="text-sm">{row.approved}</td>
                                    <td className="text-sm font-semibold">{row.days}</td>
                                    <td>
                                        {row.days <= sla.slaDays ? (
                                            <span className="sgh-badge sgh-badge-sm sgh-badge-success">On time</span>
                                        ) : (
                                            <span className="sgh-badge sgh-badge-sm sgh-badge-danger">Over target</span>
                                        )}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </Card>
            </ReportBody>
        </>
    );
};

const AuditTrail = ({ tableRef }: { tableRef: (element: HTMLDivElement | null) => void }) => (
    <>
        <ReportHeader
            title="Audit Trail"
            description="Workflow decisions — who approved, rejected, or sent back a claim, with timestamps and comments. Required for internal and external audits to demonstrate a clear chain of accountability."
        />
        <ReportBody>
            <ReportFilters
                filters={[
                    { label: 'From', value: 'Oct 01, 2026' },
                    { label: 'To', value: 'Oct 31, 2026' },
                    { label: 'Action', value: 'All actions' },
                ]}
            />
            <Card
                title="Workflow Actions"
                cardRef={tableRef}
                aside={<span className="sgh-badge sgh-badge-sm sgh-badge-outline">248 records</span>}
                bodyClassName="sgh-card-table"
            >
                <table className="sgh-table sgh-table-border">
                    <thead>
                        <tr>
                            {['Request', 'Stage', 'Actioned By', 'Action', 'Comment', 'Date & Time'].map((column) => (
                                <TableLabel key={column}>{column}</TableLabel>
                            ))}
                        </tr>
                    </thead>
                    <tbody>
                        {auditActions.map((row, index) => (
                            <tr key={index}>
                                <td className="text-sm text-secondary-foreground">#{row.request}</td>
                                <td className="text-sm">{row.stage}</td>
                                <td className="text-sm font-medium">{row.by}</td>
                                <td>
                                    <Pop start={AUDIT_PAGE + 8 + index * 5}>
                                        <span className={`sgh-badge sgh-badge-sm ${actionLabels[row.action].badge}`}>
                                            {actionLabels[row.action].label}
                                        </span>
                                    </Pop>
                                </td>
                                <td className="text-sm text-secondary-foreground">{row.comment}</td>
                                <td className="text-sm">{row.at}</td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </Card>
        </ReportBody>
    </>
);

export const ItReports = () => {
    const frame = useCurrentFrame();
    const { rootRef, anchor, rects } = useAnchors<'kpis' | 'audit'>();

    return (
        <Stage
            url={`${tenant.host}/reports/${frame >= AUDIT_PAGE ? 'audit-trail' : 'workflow-sla'}`}
            camera={[
                { frame: 0, zoom: 1.25, focus: centerOf(rects.kpis, { x: 0, y: 40 }), duration: 1 },
                { frame: AUDIT_PAGE, zoom: 1.25, focus: centerOf(rects.audit, { x: 0, y: -20 }), duration: 22 },
            ]}
        >
            <AppShell rootRef={rootRef} user={admin} active="reports">
                <PageSequence
                    pages={[
                        { at: SLA_PAGE, content: <WorkflowSla frame={frame} kpisRef={anchor('kpis')} /> },
                        { at: AUDIT_PAGE, content: <AuditTrail tableRef={anchor('audit')} /> },
                    ]}
                />
            </AppShell>
        </Stage>
    );
};
