import { useCallback, useLayoutEffect, useRef, useState } from 'react';
import type { Point } from './motion';

export type Rect = { x: number; y: number; width: number; height: number };

const measure = (element: HTMLElement, root: HTMLElement): Rect => {
    let x = 0;
    let y = 0;
    let node: HTMLElement | null = element;

    while (node && node !== root) {
        x += node.offsetLeft;
        y += node.offsetTop;
        node = node.offsetParent as HTMLElement | null;
    }

    const scroller = element.closest<HTMLElement>('[data-scroll]');

    return {
        x,
        y: y - Number(scroller?.dataset.scroll ?? 0),
        width: element.offsetWidth,
        height: element.offsetHeight,
    };
};

const sameRects = (a: Record<string, Rect>, b: Record<string, Rect>): boolean => JSON.stringify(a) === JSON.stringify(b);

/**
 * Tracks the on-screen position of named elements relative to the app
 * screen, so the cursor and camera can target real UI instead of
 * hand-tuned coordinates. Offsets ignore CSS transforms, which keeps the
 * camera zoom from feeding back into the measurements.
 */
export const useAnchors = <Name extends string>() => {
    const rootRef = useRef<HTMLDivElement>(null);
    const elements = useRef(new Map<Name, HTMLElement>());
    const callbacks = useRef(new Map<Name, (element: HTMLElement | null) => void>());
    const [rects, setRects] = useState<Partial<Record<Name, Rect>>>({});

    const anchor = useCallback((name: Name) => {
        let callback = callbacks.current.get(name);

        if (!callback) {
            callback = (element: HTMLElement | null) => {
                if (element) {
                    elements.current.set(name, element);
                } else {
                    elements.current.delete(name);
                }
            };
            callbacks.current.set(name, callback);
        }

        return callback;
    }, []);

    useLayoutEffect(() => {
        const root = rootRef.current;

        if (!root) {
            return;
        }

        const next: Record<string, Rect> = {};
        elements.current.forEach((element, name) => {
            next[name] = measure(element, root);
        });

        setRects((previous) => (sameRects(previous as Record<string, Rect>, next) ? previous : (next as Partial<Record<Name, Rect>>)));
    });

    return { rootRef, anchor, rects };
};

export const centerOf = (rect: Rect | undefined, offset: Point = { x: 0, y: 0 }): Point | undefined =>
    rect && { x: rect.x + rect.width / 2 + offset.x, y: rect.y + rect.height / 2 + offset.y };
