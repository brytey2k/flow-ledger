import { Img, useCurrentFrame } from 'remotion';
import { IconArrowRight, IconChevronRight } from '@tabler/icons-react';
import { Backdrop } from '../../components/Stage';
import { requestWorkflows, setupStages } from '../../data';
import { useIsPortrait } from '../../lib/format';
import { enter } from '../../lib/motion';
import { logos } from '../../logos';

export const SETUP_OUTRO_DURATION = 170;

export const SetupOutro = () => {
    const frame = useCurrentFrame();
    const portrait = useIsPortrait();
    const tagline = enter(frame, 62, 20);
    const next = enter(frame, 92, 20);

    return (
        <Backdrop>
            <div className={`flex size-full flex-col items-center justify-center gap-14 ${portrait ? 'px-10 pb-[560px]' : ''}`}>
                <div className={`flex flex-col rounded-3xl bg-background shadow-2xl ${portrait ? 'gap-9 px-8 py-9' : 'gap-6 px-12 py-10'}`}>
                    {requestWorkflows.map((workflow, row) => {
                        const steps = ['Requester', ...setupStages.slice(0, -1).map((stage) => stage.name), workflow.last];

                        return (
                            <div key={workflow.name} className={`flex items-center gap-4 ${portrait ? 'flex-wrap gap-y-3' : ''}`}>
                                <span
                                    className={`sgh-badge ${workflow.badge} justify-center px-3 py-2 text-2xl ${portrait ? 'basis-full' : 'w-44'}`}
                                >
                                    {workflow.type}
                                </span>
                                {steps.map((step, index) => {
                                    const progress = enter(frame, 6 + row * 10 + index * 5, 14);

                                    return (
                                        <div
                                            key={step}
                                            className={`flex items-center ${portrait ? 'gap-2' : 'gap-4'}`}
                                            style={{ opacity: progress, transform: `scale(${0.7 + progress * 0.3})` }}
                                        >
                                            {index > 0 && <IconChevronRight size={26} className="text-muted-foreground" />}
                                            <div
                                                className={`flex items-center gap-3 rounded-2xl border border-border ${portrait ? 'px-4 py-3' : 'px-5 py-4'}`}
                                            >
                                                <span className="sgh-badge sgh-badge-primary px-2.5 text-lg">
                                                    {String.fromCharCode(97 + index)}
                                                </span>
                                                <span className="text-2xl font-medium">{step}</span>
                                            </div>
                                        </div>
                                    );
                                })}
                            </div>
                        );
                    })}
                </div>
                <div
                    className="flex flex-col items-center gap-8"
                    style={{ opacity: tagline, transform: `translateY(${(1 - tagline) * 24}px)` }}
                >
                    <p
                        className={`text-center text-5xl font-semibold tracking-tight text-[var(--auth-panel-foreground)] ${portrait ? 'leading-tight' : ''}`}
                    >
                        Configured once. Every request follows its path.
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
