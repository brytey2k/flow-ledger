import type { ReactNode } from 'react';
import { IconChevronRightFilled, IconFileArrowRight } from '@tabler/icons-react';
import { Select } from './ui';

/**
 * Heading shared by the report pages: breadcrumb, title, description and
 * the CSV/PDF export buttons (reports/partials/export-buttons.blade.php).
 */
export const ReportHeader = ({ title, description }: { title: string; description: string }) => (
    <div className="sgh-container-fixed">
        <div className="flex items-end justify-between gap-5 pb-7.5">
            <div className="flex flex-col justify-center gap-2">
                <div className="flex items-center gap-2 text-sm text-secondary-foreground">
                    <span>Reports</span>
                    <IconChevronRightFilled size={12} />
                    <span>{title}</span>
                </div>
                <h1 className="text-xl font-medium leading-none">{title}</h1>
                <p className="max-w-2xl text-sm text-secondary-foreground">{description}</p>
            </div>
            <div className="flex items-center gap-2">
                <span className="sgh-btn sgh-btn-sm sgh-btn-light">
                    <IconFileArrowRight size={14} className="mr-1 text-success" />
                    CSV
                </span>
                <span className="sgh-btn sgh-btn-sm sgh-btn-light">
                    <IconFileArrowRight size={14} className="mr-1 text-destructive" />
                    PDF
                </span>
            </div>
        </div>
    </div>
);

/**
 * The filter card at the top of each report.
 */
export const ReportFilters = ({ filters }: { filters: { label: string; value: string }[] }) => (
    <div className="sgh-card p-5">
        <div className="flex items-end gap-4">
            {filters.map((filter) => (
                <div key={filter.label} className="flex w-44 flex-col gap-1.5">
                    <label className="text-xs font-medium text-secondary-foreground">{filter.label}</label>
                    <Select value={filter.value} placeholder="" />
                </div>
            ))}
            <span className="sgh-btn sgh-btn-primary sgh-btn-sm">Apply</span>
        </div>
    </div>
);

export const ReportBody = ({ children }: { children: ReactNode }) => (
    <div className="sgh-container-fixed pb-7.5">
        <div className="grid gap-7.5">{children}</div>
    </div>
);
