import { useCurrentFrame } from 'remotion';
import { easeInOut, tween, type Point } from '../lib/motion';

/**
 * Move the cursor to `to`, starting at `frame`. A missing target (an
 * anchor that has not been measured yet) keeps the cursor where it is.
 */
export type CursorMove = { frame: number; to?: Point; duration?: number };

const CLICK_FRAMES = 10;

const positionAt = (frame: number, start: Point, moves: CursorMove[]): Point => {
    let position = start;

    for (const move of moves) {
        if (!move.to) {
            continue;
        }

        const duration = move.duration ?? 18;
        const progress = easeInOut(Math.min(1, Math.max(0, (frame - move.frame) / duration)));
        position = {
            x: position.x + (move.to.x - position.x) * progress,
            y: position.y + (move.to.y - position.y) * progress,
        };
    }

    return position;
};

/**
 * Whether a click registered on `clickFrame` is currently pressed — used by
 * buttons to sync their pressed state with the cursor.
 */
export const isPressed = (frame: number, clickFrame: number): boolean => frame >= clickFrame && frame < clickFrame + CLICK_FRAMES / 2;

export const Cursor = ({
    start,
    moves,
    clicks = [],
    hideAfter,
}: {
    start: Point;
    moves: CursorMove[];
    clicks?: number[];
    hideAfter?: number;
}) => {
    const frame = useCurrentFrame();
    const position = positionAt(frame, start, moves);
    const activeClick = clicks.find((click) => frame >= click && frame < click + CLICK_FRAMES * 2);
    const press = activeClick === undefined ? 0 : tween(frame, activeClick, CLICK_FRAMES, 1, 0);
    const ripple = activeClick === undefined ? 0 : tween(frame, activeClick, CLICK_FRAMES * 2, 0, 1);
    const opacity = hideAfter === undefined ? 1 : tween(frame, hideAfter, 8, 1, 0);

    return (
        <div
            className="pointer-events-none absolute left-0 top-0 z-50"
            style={{ transform: `translate(${position.x}px, ${position.y}px)`, opacity }}
        >
            {activeClick !== undefined && (
                <span
                    className="absolute rounded-full border-2 border-primary"
                    style={{
                        width: 44,
                        height: 44,
                        left: -22,
                        top: -22,
                        opacity: 1 - ripple,
                        transform: `scale(${0.3 + ripple})`,
                    }}
                />
            )}
            <svg
                width="26"
                height="26"
                viewBox="0 0 24 24"
                style={{
                    transform: `scale(${1 - press * 0.18})`,
                    transformOrigin: '3px 2px',
                    filter: 'drop-shadow(0 2px 3px rgb(0 0 0 / 0.35))',
                    marginLeft: -3,
                    marginTop: -2,
                }}
            >
                <path
                    d="M3 2 L3 19 L7.6 14.8 L10.6 21.6 L13.6 20.3 L10.7 13.6 L17 13.6 Z"
                    fill="#111"
                    stroke="#fff"
                    strokeWidth="1.6"
                    strokeLinejoin="round"
                />
            </svg>
        </div>
    );
};
