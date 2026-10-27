import type { ReactNode } from 'react';
import { Img, useCurrentFrame } from 'remotion';
import { IconBrandWhatsapp, IconPhone, IconX } from '@tabler/icons-react';
import { Backdrop } from '../../components/Stage';
import { promo } from '../../data';
import { enter } from '../../lib/motion';
import { logos } from '../../logos';

const TEAL = 'oklch(0.72 0.13 168)';

const Rise = ({ start, children, className = '' }: { start: number; children: ReactNode; className?: string }) => {
    const frame = useCurrentFrame();
    const progress = enter(frame, start, 16);

    return (
        <div className={className} style={{ opacity: progress, transform: `translateY(${(1 - progress) * 40}px)` }}>
            {children}
        </div>
    );
};

/**
 * Full-frame card laid out above the caption band of a portrait frame.
 */
const PromoCard = ({ children }: { children: ReactNode }) => (
    <Backdrop>
        <div className="flex size-full flex-col items-center justify-center gap-12 px-20 pb-[560px] pt-40 text-center">{children}</div>
    </Backdrop>
);

/**
 * Opening hook: the pain FlowLedger removes, then the ways it shows up.
 */
export const PromoHook = () => (
    <PromoCard>
        <div className="flex flex-col items-center gap-2 text-[124px] font-semibold leading-[1.02] tracking-tight text-[var(--auth-panel-foreground)]">
            <Rise start={2}>Still chasing</Rise>
            <Rise start={9}>
                <span style={{ color: TEAL }}>signatures</span>
            </Rise>
            <Rise start={16}>for staff advances?</Rise>
        </div>
        <div className="flex flex-wrap justify-center gap-4">
            {promo.pains.map((pain, index) => (
                <Rise key={pain} start={34 + index * 6}>
                    <span className="flex items-center gap-3 rounded-full border border-[var(--auth-panel-border)] bg-white/5 px-7 py-3.5 text-3xl text-[var(--auth-panel-muted)]">
                        <IconX size={26} className="text-[oklch(0.68_0.19_25)]" />
                        {pain}
                    </span>
                </Rise>
            ))}
        </div>
    </PromoCard>
);

/**
 * The product, and what it promises.
 */
export const PromoReveal = () => {
    const frame = useCurrentFrame();
    const logo = enter(frame, 0, 14);

    return (
        <PromoCard>
            <Img src={logos.dark} style={{ height: 128, opacity: logo, transform: `scale(${0.8 + logo * 0.2})` }} />
            <Rise start={14} className="flex flex-col items-center gap-5">
                <p className="text-[64px] font-semibold leading-tight tracking-tight text-[var(--auth-panel-foreground)]">
                    Advances, expenses and retirements.
                </p>
                <p className="text-[64px] font-semibold leading-tight tracking-tight" style={{ color: TEAL }}>
                    Approved in under a day.
                </p>
            </Rise>
        </PromoCard>
    );
};

/**
 * Call to action: who it is for and how to get started.
 */
export const PromoCta = () => {
    const frame = useCurrentFrame();
    const pulse = 1 + Math.max(0, Math.sin((frame - 40) / 9)) * 0.025 * Number(frame > 40);

    return (
        <PromoCard>
            <Rise start={0}>
                <Img src={logos.dark} style={{ height: 84 }} />
            </Rise>
            <Rise start={8}>
                <p className="text-[112px] font-semibold leading-none tracking-tight text-[var(--auth-panel-foreground)]">
                    Get started today
                </p>
            </Rise>
            <Rise start={18}>
                <div
                    className="flex flex-col items-center gap-4 rounded-[36px] px-14 py-9"
                    style={{ background: TEAL, transform: `scale(${pulse})` }}
                >
                    <span className="flex items-center gap-4 text-4xl font-medium text-[oklch(0.2_0.04_168)]">
                        <IconPhone size={38} />
                        Call or WhatsApp
                        <IconBrandWhatsapp size={38} />
                    </span>
                    <span className="font-mono text-[76px] font-semibold leading-none tracking-tight text-[oklch(0.16_0.03_168)]">
                        {promo.phone}
                    </span>
                </div>
            </Rise>
            <Rise start={28}>
                <p className="text-4xl text-[var(--auth-panel-muted)]">{promo.audience}</p>
            </Rise>
        </PromoCard>
    );
};
