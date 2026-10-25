import { Img, staticFile, useCurrentFrame } from 'remotion';
import { IconChevronRight } from '@tabler/icons-react';
import { Backdrop } from '../components/Stage';
import { statusBadgeClasses, statusLabels, type RequestStatus } from '../data';
import { enter } from '../lib/motion';

export const OUTRO_DURATION = 160;

const lifecycle: RequestStatus[] = ['draft', 'in_workflow', 'approved', 'disbursed', 'retired'];

export const Outro = () => {
    const frame = useCurrentFrame();
    const tagline = enter(frame, 62, 20);

    return (
        <Backdrop>
            <div className="flex size-full flex-col items-center justify-center gap-16">
                <div className="flex items-center gap-5 rounded-3xl bg-background px-10 py-8 shadow-2xl">
                    {lifecycle.map((status, index) => {
                        const progress = enter(frame, 8 + index * 9, 14);

                        return (
                            <div
                                key={status}
                                className="flex items-center gap-5"
                                style={{ opacity: progress, transform: `scale(${0.7 + progress * 0.3})` }}
                            >
                                {index > 0 && <IconChevronRight size={28} className="text-muted-foreground" />}
                                <span className={`sgh-badge ${statusBadgeClasses[status]} px-5 py-2 text-2xl`}>{statusLabels[status]}</span>
                            </div>
                        );
                    })}
                </div>
                <div
                    className="flex flex-col items-center gap-8"
                    style={{ opacity: tagline, transform: `translateY(${(1 - tagline) * 24}px)` }}
                >
                    <p className="text-5xl font-semibold tracking-tight text-[var(--auth-panel-foreground)]">
                        Every advance approved, paid and accounted for.
                    </p>
                    <Img src={staticFile('flowledger_logo_dark.png')} style={{ height: 56 }} />
                </div>
            </div>
        </Backdrop>
    );
};
