import type { ReactNode } from 'react';
import { Img, staticFile, useCurrentFrame } from 'remotion';
import { Backdrop } from '../components/Stage';
import { enter } from '../lib/motion';

export const INTRO_DURATION = 120;

const Rise = ({ start, children, className = '' }: { start: number; children: ReactNode; className?: string }) => {
    const frame = useCurrentFrame();
    const progress = enter(frame, start, 20);

    return (
        <div className={className} style={{ opacity: progress, transform: `translateY(${(1 - progress) * 30}px)` }}>
            {children}
        </div>
    );
};

/**
 * Opening title card: logo, title, subtitle and the numbered steps ahead.
 */
export const TitleCard = ({ title, subtitle, steps }: { title: string; subtitle: string; steps: string[] }) => (
    <Backdrop>
        <div className="flex size-full flex-col items-center justify-center gap-10 text-center">
            <Rise start={4}>
                <Img src={staticFile('flowledger_logo_dark.png')} style={{ height: 64 }} />
            </Rise>
            <Rise start={12} className="flex flex-col gap-5">
                <h1 className="text-[84px] font-semibold leading-none tracking-tight text-[var(--auth-panel-foreground)]">{title}</h1>
                <p className="text-3xl text-[var(--auth-panel-muted)]">{subtitle}</p>
            </Rise>
            <div className="mt-4 flex items-center gap-4">
                {steps.map((step, index) => (
                    <Rise key={step} start={30 + index * 6}>
                        <span className="flex items-center gap-3 rounded-full border border-[var(--auth-panel-border)] bg-white/5 px-6 py-3 text-2xl text-[var(--auth-panel-foreground)]">
                            <span className="font-mono text-xl text-[oklch(0.72_0.13_168)]">{String(index + 1).padStart(2, '0')}</span>
                            {step}
                        </span>
                    </Rise>
                ))}
            </div>
        </div>
    </Backdrop>
);

export const Intro = () => (
    <TitleCard
        title="The advance request flow"
        subtitle="From first request to final retirement, in five steps."
        steps={['Raise', 'Submit', 'Approve', 'Disburse', 'Retire']}
    />
);
