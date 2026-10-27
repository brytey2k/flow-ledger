import { AbsoluteFill, useCurrentFrame } from 'remotion';
import { PORTRAIT_BANDS } from '../lib/format';
import { FPS, tween } from '../lib/motion';
import { narration } from '../narration';
import type { Segment } from '../Series';
import voiceover from '../voiceover.json';

const TEAL = 'oklch(0.72 0.13 168)';
const FADE_FRAMES = 12;
const CAPTION_CHARACTERS = 46;

const voiceClips: Record<string, { seconds: number }> = voiceover;

/**
 * Splits `text` into `parts` runs of whole words of roughly equal length.
 */
const evenSplit = (text: string, parts: number): string[] => {
    const words = text.split(' ');
    const target = text.length / parts;

    return words.reduce<string[]>((runs, word) => {
        const last = runs[runs.length - 1];

        if (last !== undefined && (last.length < target || runs.length === parts)) {
            runs[runs.length - 1] = `${last} ${word}`;

            return runs;
        }

        return [...runs, word];
    }, []);
};

/**
 * Splits a narration line into caption cards of at most about
 * CAPTION_CHARACTERS. Cards break at sentence ends and, where they can,
 * after a comma or colon, so each one reads as a phrase.
 */
const captionChunks = (text: string): string[] =>
    text.split(/(?<=[.!?])\s+/).flatMap((sentence) =>
        sentence
            .split(/(?<=[,:;])\s+/)
            .flatMap((clause) =>
                clause.length <= CAPTION_CHARACTERS ? [clause] : evenSplit(clause, Math.ceil(clause.length / CAPTION_CHARACTERS)),
            )
            .reduce<string[]>((chunks, piece) => {
                const last = chunks[chunks.length - 1];

                if (last !== undefined && `${last} ${piece}`.length <= CAPTION_CHARACTERS) {
                    chunks[chunks.length - 1] = `${last} ${piece}`;

                    return chunks;
                }

                return [...chunks, piece];
            }, []),
    );

/**
 * Caption cards for one voice clip, each timed by its share of the
 * clip's characters.
 */
const captionTimeline = (segment: Segment, voiceLeadFrames: number): { text: string; from: number; to: number }[] => {
    const text = segment.voice ? narration[segment.voice] : undefined;
    const clip = segment.voice ? voiceClips[segment.voice] : undefined;

    if (!text || !clip) {
        return [];
    }

    const chunks = captionChunks(text);
    const totalCharacters = chunks.reduce((sum, chunk) => sum + chunk.length, 0);
    const clipFrames = clip.seconds * FPS;
    let from = segment.start + voiceLeadFrames;

    return chunks.map((chunk) => {
        const to = from + (chunk.length / totalCharacters) * clipFrames;
        const card = { text: chunk, from, to };
        from = to;

        return card;
    });
};

const fadeInOut = (frame: number, from: number, to: number): number =>
    Math.min(tween(frame, from, FADE_FRAMES, 0, 1), tween(frame, to - FADE_FRAMES, FADE_FRAMES, 1, 0));

/**
 * Portrait-only layer over the series: the current chapter above the app
 * window, and captions of the narration below it, for viewers watching
 * with the sound off.
 */
export const PortraitOverlay = ({ segments, voiceLeadFrames }: { segments: Segment[]; voiceLeadFrames: number }) => {
    const frame = useCurrentFrame();
    const scene = segments.find((segment) => segment.chapter && frame >= segment.start && frame < segment.start + segment.durationInFrames);
    const caption = segments
        .flatMap((segment) => captionTimeline(segment, voiceLeadFrames))
        .find((card) => frame >= card.from && frame < card.to);

    return (
        <AbsoluteFill className="pointer-events-none">
            {scene?.chapter && (
                <div
                    className="absolute inset-x-0 flex flex-col items-center gap-3 px-16 text-center"
                    style={{ top: PORTRAIT_BANDS.headerTop, opacity: fadeInOut(frame, scene.start, scene.start + scene.durationInFrames) }}
                >
                    <span className="font-mono text-2xl uppercase tracking-widest" style={{ color: TEAL }}>
                        {scene.chapter.part}
                    </span>
                    <span className="text-[64px] font-semibold leading-none tracking-tight text-[var(--auth-panel-foreground)]">
                        {scene.chapter.title}
                    </span>
                </div>
            )}
            {caption && (
                <div className="absolute inset-x-0 flex justify-center px-16" style={{ top: PORTRAIT_BANDS.captionTop }}>
                    <span
                        key={caption.from}
                        className="rounded-2xl bg-black/60 px-7 py-4 text-center text-[52px] font-semibold leading-tight text-white"
                        style={{ opacity: tween(frame, caption.from, 4, 0, 1) }}
                    >
                        {caption.text}
                    </span>
                </div>
            )}
        </AbsoluteFill>
    );
};
