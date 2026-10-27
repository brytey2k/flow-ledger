import { Easing, interpolate, spring } from 'remotion';

export const FPS = 30;

export const easeInOut = Easing.bezier(0.65, 0, 0.35, 1);
export const easeOut = Easing.bezier(0.16, 1, 0.3, 1);

export type Point = { x: number; y: number };

/**
 * Clamped interpolation between two values over a frame window.
 */
export const tween = (
    frame: number,
    start: number,
    duration: number,
    from: number,
    to: number,
    easing: (t: number) => number = easeInOut,
): number =>
    interpolate(frame, [start, start + duration], [from, to], {
        easing,
        extrapolateLeft: 'clamp',
        extrapolateRight: 'clamp',
    });

/**
 * 0 → 1 progress for an element entering at `start`, with a soft overshoot.
 */
export const enter = (frame: number, start: number, damping = 18): number =>
    spring({ frame: frame - start, fps: FPS, config: { damping, mass: 0.6 } });

/**
 * The part of `text` that has been typed by `frame`, starting at `start`.
 */
export const typed = (text: string, frame: number, start: number, charsPerFrame = 0.9): string =>
    text.slice(0, Math.max(0, Math.floor((frame - start) * charsPerFrame)));

/**
 * Frame on which typing `text` finishes.
 */
export const typingEnds = (text: string, start: number, charsPerFrame = 0.9): number => start + Math.ceil(text.length / charsPerFrame);

export const money = (amount: number): string => amount.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

/**
 * Animated count-up from `from` to `to`, in whole units until it lands.
 */
export const countUp = (frame: number, start: number, duration: number, from: number, to: number): number =>
    frame >= start + duration ? to : Math.round(tween(frame, start, duration, from, to, easeOut));
