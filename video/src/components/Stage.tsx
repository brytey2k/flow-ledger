import type { ReactNode } from 'react';
import { AbsoluteFill, useCurrentFrame, useVideoConfig } from 'remotion';
import { IconLock } from '@tabler/icons-react';
import { PORTRAIT_BANDS, PORTRAIT_SCREEN_WIDTH } from '../lib/format';
import { easeInOut, type Point } from '../lib/motion';

export const SCREEN_WIDTH = 1440;
export const SCREEN_HEIGHT = 864;

const CHROME_HEIGHT = 40;
const WINDOW_HEIGHT = SCREEN_HEIGHT + CHROME_HEIGHT;
const BASE_SCALE = 1.1;

/**
 * Share of each camera push-in kept in portrait. The portrait screen
 * already fills the frame's width, so any push-in crops its sides; at 0
 * the whole screen stays in view and the cursor carries the eye.
 */
const PORTRAIT_ZOOM_STRENGTH = 0;

/**
 * A camera move starting at `frame`: zoom is relative to the resting
 * framing, focus is a point on the app screen.
 */
export type CameraKey = { frame: number; zoom: number; focus?: Point; duration?: number };

type Screen = { width: number; height: number };

const LANDSCAPE_SCREEN: Screen = { width: SCREEN_WIDTH, height: SCREEN_HEIGHT };

/**
 * The portrait screen fills the frame's width at rest and the window
 * fills the band between the header and the captions.
 */
const portraitScreen = (frameWidth: number): Screen & { baseScale: number } => {
    const baseScale = frameWidth / PORTRAIT_SCREEN_WIDTH;

    return {
        width: PORTRAIT_SCREEN_WIDTH,
        height: Math.round((PORTRAIT_BANDS.stageBottom - PORTRAIT_BANDS.stageTop) / baseScale - CHROME_HEIGHT),
        baseScale,
    };
};

const lerp = (from: number, to: number, progress: number): number => from + (to - from) * progress;

const cameraAt = (frame: number, keys: CameraKey[], screen: Screen): { zoom: number; focus: Point } => {
    const center: Point = { x: screen.width / 2, y: screen.height / 2 };
    let zoom = 1;
    let focus = center;

    for (const key of keys) {
        const duration = key.duration ?? 24;
        const progress = easeInOut(Math.min(1, Math.max(0, (frame - key.frame) / duration)));
        const target = key.focus ?? center;

        zoom = lerp(zoom, key.zoom, progress);
        focus = { x: lerp(focus.x, target.x, progress), y: lerp(focus.y, target.y, progress) };
    }

    return { zoom, focus };
};

const clamp = (value: number, min: number, max: number): number => Math.min(max, Math.max(min, value));

/**
 * Window position for a landscape frame: the resting framing leaves an
 * even margin, and pushing in never reveals more than that margin.
 */
const landscapeTranslate = (scale: number, focus: Point, width: number, height: number): Point => {
    const marginX = (width - SCREEN_WIDTH * BASE_SCALE) / 2;
    const marginY = (height - WINDOW_HEIGHT * BASE_SCALE) / 2;

    return {
        x: clamp(width / 2 - scale * focus.x, width - scale * SCREEN_WIDTH - marginX, marginX),
        y: clamp(height / 2 - scale * (focus.y + CHROME_HEIGHT), height - scale * WINDOW_HEIGHT - marginY, marginY),
    };
};

/**
 * Window position for a portrait frame, relative to the band between the
 * chapter header and the captions: at rest the window fills the band
 * exactly, and pushing in pans to the focus without leaving it.
 */
const portraitTranslate = (scale: number, focus: Point, width: number, screen: Screen): Point => {
    const bandHeight = PORTRAIT_BANDS.stageBottom - PORTRAIT_BANDS.stageTop;

    return {
        x: clamp(width / 2 - scale * focus.x, width - scale * screen.width, 0),
        y: clamp(bandHeight / 2 - scale * (focus.y + CHROME_HEIGHT), bandHeight - scale * (screen.height + CHROME_HEIGHT), 0),
    };
};

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
 * the resting margins. In portrait the window is clipped to its band so
 * it never covers the chapter header or the captions.
 */
export const Stage = ({ url, camera = [], children }: { url: string; camera?: CameraKey[]; children: ReactNode }) => {
    const frame = useCurrentFrame();
    const { width, height } = useVideoConfig();
    const portrait = height > width;
    const screen = portrait ? portraitScreen(width) : LANDSCAPE_SCREEN;
    const { zoom, focus } = cameraAt(frame, camera, screen);
    const scale = portrait ? portraitScreen(width).baseScale * (1 + (zoom - 1) * PORTRAIT_ZOOM_STRENGTH) : BASE_SCALE * zoom;
    const { x: translateX, y: translateY } = portrait
        ? portraitTranslate(scale, focus, width, screen)
        : landscapeTranslate(scale, focus, width, height);

    const browser = (
        <div
            className="absolute left-0 top-0 overflow-hidden rounded-[14px] bg-background"
            style={{
                width: screen.width,
                height: screen.height + CHROME_HEIGHT,
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
                <div className="mx-auto flex h-6 w-[520px] max-w-[60%] items-center justify-center gap-1.5 rounded-md bg-background text-xs text-muted-foreground">
                    <IconLock size={12} />
                    {url}
                </div>
                <div className="w-[52px]" />
            </div>
            <div className="relative" style={{ width: screen.width, height: screen.height }}>
                {children}
            </div>
        </div>
    );

    return (
        <Backdrop>
            {portrait ? (
                <div
                    className="absolute inset-x-0 overflow-hidden"
                    style={{ top: PORTRAIT_BANDS.stageTop, height: PORTRAIT_BANDS.stageBottom - PORTRAIT_BANDS.stageTop }}
                >
                    {browser}
                </div>
            ) : (
                browser
            )}
        </Backdrop>
    );
};
