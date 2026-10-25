import { useCurrentFrame } from 'remotion';
import { easeOut, enter, tween } from '../lib/motion';

const CAPTION_FRAMES = 105;

/**
 * Lower-third step caption shown over the browser window while the scene
 * settles in, then cleared so it never covers the action.
 */
export const Caption = ({ step, title, body }: { step: number; title: string; body: string }) => {
    const frame = useCurrentFrame();
    const progress = enter(frame, 6, 20);
    const exit = tween(frame, CAPTION_FRAMES, 14, 0, 1, easeOut);

    return (
        <div
            className="absolute bottom-14 left-14 flex max-w-[620px] items-start gap-5 rounded-2xl border px-7 py-6"
            style={{
                background: 'oklch(0.19 0.012 255 / 0.92)',
                borderColor: 'var(--auth-panel-border)',
                boxShadow: '0 24px 60px -12px rgb(0 0 0 / 0.5)',
                opacity: progress * (1 - exit),
                transform: `translateY(${(1 - progress) * 40 + exit * 20}px)`,
            }}
        >
            <span className="font-mono text-[42px] font-semibold leading-none text-[oklch(0.72_0.13_168)]">
                {String(step).padStart(2, '0')}
            </span>
            <div className="flex flex-col gap-1.5">
                <span className="text-[30px] font-semibold leading-tight tracking-tight text-[var(--auth-panel-foreground)]">{title}</span>
                <span className="text-xl leading-snug text-[var(--auth-panel-muted)]">{body}</span>
            </div>
        </div>
    );
};
