import { Img, useCurrentFrame } from 'remotion';
import { IconArrowRight, IconChevronRight } from '@tabler/icons-react';
import { Backdrop } from '../../components/Stage';
import { setupStages, setupTemplate } from '../../data';
import { enter, money } from '../../lib/motion';
import { logos } from '../../logos';

export const SETUP_OUTRO_DURATION = 170;

export const SetupOutro = () => {
    const frame = useCurrentFrame();
    const tagline = enter(frame, 62, 20);
    const next = enter(frame, 92, 20);

    return (
        <Backdrop>
            <div className="flex size-full flex-col items-center justify-center gap-14">
                <div className="flex flex-col items-center gap-7 rounded-3xl bg-background px-12 py-10 shadow-2xl">
                    <span className="text-3xl font-semibold">{setupTemplate.name}</span>
                    <div className="flex items-center gap-4">
                        {setupStages.map((stage, index) => {
                            const progress = enter(frame, 8 + index * 9, 14);

                            return (
                                <div
                                    key={stage.name}
                                    className="flex items-center gap-4"
                                    style={{ opacity: progress, transform: `scale(${0.7 + progress * 0.3})` }}
                                >
                                    {index > 0 && <IconChevronRight size={28} className="text-muted-foreground" />}
                                    <div className="flex flex-col items-start gap-3 rounded-2xl border border-border px-7 py-6">
                                        <span className="sgh-badge sgh-badge-primary px-3 text-xl">{index + 1}</span>
                                        <span className="text-3xl font-medium">{stage.name}</span>
                                        <span className="text-xl text-muted-foreground">
                                            {stage.skipBelow === undefined ? 'Every request' : `Skipped below ${money(stage.skipBelow)}`}
                                        </span>
                                    </div>
                                </div>
                            );
                        })}
                    </div>
                </div>
                <div
                    className="flex flex-col items-center gap-8"
                    style={{ opacity: tagline, transform: `translateY(${(1 - tagline) * 24}px)` }}
                >
                    <p className="text-5xl font-semibold tracking-tight text-[var(--auth-panel-foreground)]">
                        Configured once. Every request follows it.
                    </p>
                    <span
                        className="flex items-center gap-3 rounded-full border border-[var(--auth-panel-border)] bg-white/5 px-6 py-3 text-2xl text-[var(--auth-panel-foreground)]"
                        style={{ opacity: next }}
                    >
                        Next: the advance request flow
                        <IconArrowRight size={24} className="text-[oklch(0.72_0.13_168)]" />
                    </span>
                    <Img src={logos.dark} style={{ height: 48 }} />
                </div>
            </div>
        </Backdrop>
    );
};
