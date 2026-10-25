import type { ReactNode } from 'react';
import { AbsoluteFill, useCurrentFrame } from 'remotion';
import { IconLock } from '@tabler/icons-react';
import { easeInOut, type Point } from '../lib/motion';

export const SCREEN_WIDTH = 1440;
export const SCREEN_HEIGHT = 864;

const CHROME_HEIGHT = 40;
const WINDOW_HEIGHT = SCREEN_HEIGHT + CHROME_HEIGHT;
const BASE_SCALE = 1.1;
const FRAME_WIDTH = 1920;
const FRAME_HEIGHT = 1080;
const MARGIN_X = (FRAME_WIDTH - SCREEN_WIDTH * BASE_SCALE) / 2;
const MARGIN_Y = (FRAME_HEIGHT - WINDOW_HEIGHT * BASE_SCALE) / 2;

/**
 * A camera move starting at `frame`: zoom is relative to the resting
 * framing, focus is a point on the app screen.
 */
export type CameraKey = { frame: number; zoom: number; focus?: Point; duration?: number };

const SCREEN_CENTER: Point = { x: SCREEN_WIDTH / 2, y: SCREEN_HEIGHT / 2 };

const lerp = (from: number, to: number, progress: number): number => from + (to - from) * progress;

const cameraAt = (frame: number, keys: CameraKey[]): { zoom: number; focus: Point } => {
    let zoom = 1;
    let focus = SCREEN_CENTER;

    for (const key of keys) {
        const duration = key.duration ?? 24;
        const progress = easeInOut(Math.min(1, Math.max(0, (frame - key.frame) / duration)));
        const target = key.focus ?? SCREEN_CENTER;

        zoom = lerp(zoom, key.zoom, progress);
        focus = { x: lerp(focus.x, target.x, progress), y: lerp(focus.y, target.y, progress) };
    }

    return { zoom, focus };
};

const clamp = (value: number, min: number, max: number): number => Math.min(max, Math.max(min, value));

export const Backdrop = ({ children }: { children?: ReactNode }) => {
    const frame = useCurrentFrame();
    const drift = Math.sin(frame / 90) * 60;

    return (
        <AbsoluteFill
            style={{
                background: `radial-gradient(1100px 700px at ${18 + drift / 30}% ${12 + drift / 40}%, oklch(0.5 0.115 168 / 0.32), transparent 70%),
                    radial-gradient(900px 600px at 92% 100%, oklch(0.55 0.13 250 / 0.22), transparent 70%),
                    var(--auth-panel-background)`,
            }}
        >
            {children}
        </AbsoluteFill>
    );
};

/**
 * Browser window holding the app screen, framed by a camera that can
 * push in on any point of the screen without exposing empty space beyond
 * the resting margins.
 */
export const Stage = ({
    url,
    camera = [],
    children,
    overlay,
}: {
    url: string;
    camera?: CameraKey[];
    children: ReactNode;
    overlay?: ReactNode;
}) => {
    const frame = useCurrentFrame();
    const { zoom, focus } = cameraAt(frame, camera);
    const scale = BASE_SCALE * zoom;

    const translateX = clamp(FRAME_WIDTH / 2 - scale * focus.x, FRAME_WIDTH - scale * SCREEN_WIDTH - MARGIN_X, MARGIN_X);
    const translateY = clamp(
        FRAME_HEIGHT / 2 - scale * (focus.y + CHROME_HEIGHT),
        FRAME_HEIGHT - scale * WINDOW_HEIGHT - MARGIN_Y,
        MARGIN_Y,
    );

    return (
        <Backdrop>
            <div
                className="absolute left-0 top-0 overflow-hidden rounded-[14px] bg-background"
                style={{
                    width: SCREEN_WIDTH,
                    height: WINDOW_HEIGHT,
                    transformOrigin: '0 0',
                    transform: `translate(${translateX}px, ${translateY}px) scale(${scale})`,
                    boxShadow: '0 40px 120px -20px rgb(0 0 0 / 0.55), 0 0 0 1px rgb(255 255 255 / 0.08)',
                }}
            >
                <div className="flex items-center gap-4 border-b border-border bg-muted px-4" style={{ height: CHROME_HEIGHT }}>
                    <div className="flex gap-2">
                        <span className="size-3 rounded-full bg-[#ff5f57]" />
                        <span className="size-3 rounded-full bg-[#febc2e]" />
                        <span className="size-3 rounded-full bg-[#28c840]" />
                    </div>
                    <div className="mx-auto flex h-6 w-[520px] items-center justify-center gap-1.5 rounded-md bg-background text-xs text-muted-foreground">
                        <IconLock size={12} />
                        {url}
                    </div>
                    <div className="w-[52px]" />
                </div>
                <div className="relative" style={{ width: SCREEN_WIDTH, height: SCREEN_HEIGHT }}>
                    {children}
                </div>
            </div>
            {overlay}
        </Backdrop>
    );
};
