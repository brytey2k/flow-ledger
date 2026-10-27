import type { ReactNode } from 'react';
import { useCurrentFrame } from 'remotion';
import {
    IconArrowRight,
    IconBellFilled,
    IconBuildingBank,
    IconCalculatorFilled,
    IconChartLine,
    IconChartPieFilled,
    IconClockFilled,
    IconShieldCheckFilled,
    IconTrophyFilled,
} from '@tabler/icons-react';
import { AppShell } from '../../components/AppShell';
import { clickPath, Cursor } from '../../components/Cursor';
import { ReportBody, ReportFilters, ReportHeader } from '../../components/ReportPage';
import { Stage } from '../../components/Stage';
import { Card, PageSequence, Pop, TableLabel } from '../../components/ui';
import { advance, agingBuckets, cashbooks, financeDirector, outstandingAdvances, spendTrend, tenant } from '../../data';
import { centerOf, useAnchors } from '../../lib/anchors';
import { countUp, easeOut, money, tween } from '../../lib/motion';

const SPEND_CLICK = 46;
const SPEND_PAGE = SPEND_CLICK + 8;
const OUTSTANDING_PAGE = SPEND_PAGE + 100;
const CASH_PAGE = OUTSTANDING_PAGE + 92;

export const CFO_REPORTS_DURATION = CASH_PAGE + 86;

const symbol = advance.currency.symbol;

const ReportCard = ({
    icon,
    tone,
    title,
    description,
    cardRef,
}: {
    icon: ReactNode;
    tone: string;
    title: string;
    description: string;
    cardRef?: (element: HTMLDivElement | null) => void;
}) => (
    <div ref={cardRef} className="sgh-card flex flex-col gap-3 p-5">
        <div className="flex items-center gap-3">
            <div className={`flex size-10 items-center justify-center rounded-lg ${tone}`}>{icon}</div>
            <h3 className="text-sm font-semibold">{title}</h3>
        </div>
        <p className="text-xs leading-relaxed text-secondary-foreground">{description}</p>
        <span className="flex items-center gap-1 text-xs font-medium text-primary">
            View report <IconArrowRight size={12} />
        </span>
    </div>
);

const ReportsIndex = ({ spendRef }: { spendRef: (element: HTMLDivElement | null) => void }) => (
    <>
        <div className="sgh-container-fixed">
            <div className="flex flex-col gap-2 pb-7.5">
                <h1 className="text-xl font-medium leading-none">Reports</h1>
                <div className="text-sm text-secondary-foreground">
                    Select a report below to gain insights into your organisation's financial activity and workflow performance.
                </div>
            </div>
        </div>
        <div className="sgh-container-fixed grid gap-7.5">
            <div>
                <h2 className="mb-4 text-base font-semibold">Financial Visibility</h2>
                <div className="grid grid-cols-4 gap-5">
                    <ReportCard
                        icon={<IconChartPieFilled size={20} className="text-primary" />}
                        tone="bg-primary/10"
                        title="Expenditure Summary"
                        description="Spend by department, branch, or cost code over a selected period."
                    />
                    <ReportCard
                        icon={<IconClockFilled size={20} className="text-warning" />}
                        tone="bg-warning/10"
                        title="Outstanding Advances"
                        description="Staff with disbursed advances that have no approved retirement."
                    />
                    <ReportCard
                        icon={<IconCalculatorFilled size={20} className="text-success" />}
                        tone="bg-success/10"
                        title="Cash Position"
                        description="Current balance per cashbook with period in/out totals."
                    />
                    <ReportCard
                        icon={<IconBuildingBank size={20} className="text-info" />}
                        tone="bg-info/10"
                        title="Disbursement Register"
                        description="All disbursed payments in a date range. The auditor's first ask."
                    />
                </div>
            </div>
            <div>
                <h2 className="mb-4 text-base font-semibold">Executive</h2>
                <div className="grid grid-cols-4 gap-5">
                    <ReportCard
                        icon={<IconShieldCheckFilled size={20} className="text-success" />}
                        tone="bg-success/10"
                        title="Workflow SLA"
                        description="Percentage of requests approved within a configurable target."
                    />
                    <ReportCard
                        cardRef={spendRef}
                        icon={<IconChartLine size={20} className="text-primary" />}
                        tone="bg-primary/10"
                        title="Spend Trend"
                        description="Total spend per month across all request types."
                    />
                    <ReportCard
                        icon={<IconTrophyFilled size={20} className="text-warning" />}
                        tone="bg-warning/10"
                        title="Top Spenders"
                        description="Staff or departments with the highest approved spend in a period."
                    />
                    <ReportCard
                        icon={<IconBellFilled size={20} className="text-warning" />}
                        tone="bg-warning/10"
                        title="Retirement Reminders"
                        description="Advances that have triggered overdue retirement reminders."
                    />
                </div>
            </div>
        </div>
    </>
);

const CHART_MAX = 100000;

/**
 * Monthly spend bar chart, drawn to match the ApexCharts styling the app
 * uses: rounded primary bars, dashed horizontal grid, K-formatted axis.
 */
const SpendChart = ({ frame }: { frame: number }) => (
    <div className="flex h-[280px] gap-3">
        <div className="flex flex-col justify-between pb-6 text-right text-xs text-secondary-foreground">
            {[100, 75, 50, 25, 0].map((value) => (
                <span key={value}>{value}K</span>
            ))}
        </div>
        <div className="relative grow">
            <div className="absolute inset-x-0 bottom-6 top-2 flex flex-col justify-between">
                {[0, 1, 2, 3, 4].map((line) => (
                    <div key={line} className="border-t border-dashed border-border" />
                ))}
            </div>
            <div className="absolute inset-x-0 bottom-0 top-2 flex items-end justify-around">
                {spendTrend.map((month, index) => {
                    const grow = tween(frame, SPEND_PAGE + 12 + index * 4, 22, 0, 1, easeOut);

                    return (
                        <div key={month.month} className="flex h-full w-[5.5%] flex-col items-center justify-end gap-2">
                            <div
                                className="w-full rounded-t-[4px] bg-primary"
                                style={{ height: `calc((100% - 24px) * ${(month.total / CHART_MAX) * grow})` }}
                            />
                            <span className="h-4 text-xs text-secondary-foreground">{month.month}</span>
                        </div>
                    );
                })}
            </div>
        </div>
    </div>
);

const SpendTrend = ({ frame, chartRef }: { frame: number; chartRef: (element: HTMLDivElement | null) => void }) => (
    <>
        <ReportHeader
            title="Spend Trend"
            description="Total disbursed spend per month for a selected year. Reveals seasonal patterns, budget burn rate, and year-over-year changes in organisational expenditure."
        />
        <ReportBody>
            <ReportFilters
                filters={[
                    { label: 'Year', value: '2026' },
                    { label: 'Type', value: 'All' },
                ]}
            />
            <div ref={chartRef} className="sgh-card p-5">
                <div className="mb-4 flex items-center justify-between">
                    <h3 className="text-sm font-semibold">Monthly Spend — 2026</h3>
                    <span className="text-xs text-secondary-foreground">Click a bar to drill into that month</span>
                </div>
                <SpendChart frame={frame} />
            </div>
        </ReportBody>
    </>
);

const OutstandingAdvances = ({ frame, bucketsRef }: { frame: number; bucketsRef: (element: HTMLDivElement | null) => void }) => (
    <>
        <ReportHeader
            title="Outstanding Advances"
            description="Staff with disbursed advances that have no approved retirement. Includes aging buckets (0–30, 31–60, 61+ days). Compliance-critical — unretired advances represent unaccounted money."
        />
        <ReportBody>
            <div ref={bucketsRef} className="grid grid-cols-3 gap-4">
                {agingBuckets.map((bucket, index) => (
                    <Pop key={bucket.label} start={OUTSTANDING_PAGE + 6 + index * 5} className="sgh-card p-5">
                        <div className="mb-2 flex items-center justify-between">
                            <span className="text-sm text-secondary-foreground">{bucket.label}</span>
                            <span className={`sgh-badge sgh-badge-sm ${bucket.badge}`}>Aging</span>
                        </div>
                        <div className="text-2xl font-semibold">{countUp(frame, OUTSTANDING_PAGE + 8, 30, 0, bucket.count)}</div>
                        <div className="mt-1 text-xs text-secondary-foreground">Total: {money(bucket.total)}</div>
                    </Pop>
                ))}
            </div>
            <Card
                title="Outstanding Advances"
                aside={<span className="sgh-badge sgh-badge-sm sgh-badge-outline">21 advances</span>}
                bodyClassName="sgh-card-table"
            >
                <table className="sgh-table sgh-table-border">
                    <thead>
                        <tr>
                            {['#', 'Staff', 'Branch', 'Department', 'Amount', 'Disbursed On', 'Days Outstanding', 'Aging'].map((column) => (
                                <TableLabel key={column}>{column}</TableLabel>
                            ))}
                        </tr>
                    </thead>
                    <tbody>
                        {outstandingAdvances.map((row) => (
                            <tr key={row.id}>
                                <td className="text-sm text-secondary-foreground">#{row.id}</td>
                                <td className="text-sm font-medium">{row.staff}</td>
                                <td className="text-sm">{row.branch}</td>
                                <td className="text-sm">{row.department}</td>
                                <td className="text-sm font-medium">
                                    {symbol} {money(row.amount)}
                                </td>
                                <td className="text-sm">{row.disbursedOn}</td>
                                <td className="text-sm font-semibold">{row.days}</td>
                                <td>
                                    <span className={`sgh-badge sgh-badge-sm ${agingBuckets[row.bucket].badge}`}>
                                        {agingBuckets[row.bucket].label}
                                    </span>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </Card>
        </ReportBody>
    </>
);

const CashPosition = ({ frame, cardsRef }: { frame: number; cardsRef: (element: HTMLDivElement | null) => void }) => (
    <>
        <ReportHeader
            title="Cash Position"
            description="Current cash per branch, approved payments awaiting release, and the amount still free for new commitments. Period receipts and payments show movement within the selected date range."
        />
        <ReportBody>
            <div ref={cardsRef} className="grid grid-cols-3 gap-5">
                {cashbooks.map((cashbook, index) => (
                    <Pop key={cashbook.branch} start={CASH_PAGE + 6 + index * 5} className="sgh-card flex flex-col gap-4 p-5">
                        <div className="flex items-center justify-between">
                            <div>
                                <div className="text-sm font-semibold">{cashbook.branch}</div>
                                <div className="text-xs text-secondary-foreground">GHS Cashbook</div>
                            </div>
                            <IconCalculatorFilled size={24} className="text-muted-foreground" />
                        </div>
                        <div className="flex flex-col gap-1">
                            <div className="text-xs text-secondary-foreground">Current Balance</div>
                            <div className="text-2xl font-bold">
                                {symbol} {money(countUp(frame, CASH_PAGE + 8, 34, 0, cashbook.balance))}
                            </div>
                        </div>
                        <div className="grid grid-cols-2 gap-3 rounded-lg bg-muted/40 p-3">
                            <div>
                                <div className="mb-0.5 text-xs text-secondary-foreground">Approved Awaiting Release</div>
                                <div className="text-sm font-semibold text-warning">{money(cashbook.awaiting)}</div>
                            </div>
                            <div>
                                <div className="mb-0.5 text-xs text-secondary-foreground">Available Uncommitted Cash</div>
                                <div className="text-sm font-semibold text-success">
                                    {symbol} {money(cashbook.balance - cashbook.awaiting)}
                                </div>
                            </div>
                        </div>
                        <div className="grid grid-cols-2 gap-3 border-t border-border pt-3">
                            <div>
                                <div className="mb-0.5 text-xs text-secondary-foreground">Period Receipts</div>
                                <div className="text-sm font-semibold text-success">+ {money(cashbook.receipts)}</div>
                            </div>
                            <div>
                                <div className="mb-0.5 text-xs text-secondary-foreground">Period Payments</div>
                                <div className="text-sm font-semibold text-destructive">− {money(cashbook.payments)}</div>
                            </div>
                        </div>
                        <span className="text-xs text-primary">{cashbook.entries} entries in period →</span>
                    </Pop>
                ))}
            </div>
        </ReportBody>
    </>
);

export const CfoReports = () => {
    const frame = useCurrentFrame();
    const { rootRef, anchor, rects } = useAnchors<'spend' | 'chart' | 'buckets' | 'cash'>();
    const path = clickPath([{ at: SPEND_CLICK, to: centerOf(rects.spend, { x: 40, y: 10 }) }], 20);
    const page =
        frame >= CASH_PAGE
            ? 'cash-position'
            : frame >= OUTSTANDING_PAGE
              ? 'outstanding-advances'
              : frame >= SPEND_PAGE
                ? 'spend-trend'
                : '';

    return (
        <Stage
            url={`${tenant.host}/reports${page ? `/${page}` : ''}`}
            camera={[
                { frame: 4, zoom: 1.1, focus: centerOf(rects.spend, { x: -40, y: -80 }), duration: 30 },
                { frame: SPEND_PAGE, zoom: 1.25, focus: centerOf(rects.chart, { x: 0, y: -20 }), duration: 26 },
                { frame: OUTSTANDING_PAGE, zoom: 1.2, focus: centerOf(rects.buckets, { x: 0, y: 150 }), duration: 22 },
                { frame: CASH_PAGE, zoom: 1.3, focus: centerOf(rects.cash, { x: 0, y: -10 }), duration: 22 },
            ]}
        >
            <AppShell
                rootRef={rootRef}
                user={financeDirector}
                active="reports"
                overlay={<Cursor start={{ x: 700, y: 640 }} moves={path.moves} clicks={path.clicks} hideAfter={SPEND_PAGE + 10} />}
            >
                <PageSequence
                    pages={[
                        { at: 0, content: <ReportsIndex spendRef={anchor('spend')} /> },
                        { at: SPEND_PAGE, content: <SpendTrend frame={frame} chartRef={anchor('chart')} /> },
                        { at: OUTSTANDING_PAGE, content: <OutstandingAdvances frame={frame} bucketsRef={anchor('buckets')} /> },
                        { at: CASH_PAGE, content: <CashPosition frame={frame} cardsRef={anchor('cash')} /> },
                    ]}
                />
            </AppShell>
        </Stage>
    );
};
