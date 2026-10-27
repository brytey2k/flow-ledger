import type { ComponentType, ReactNode } from 'react';
import { Fragment } from 'react';
import { AbsoluteFill, Html5Audio, Sequence, staticFile } from 'remotion';
import { linearTiming, TransitionSeries } from '@remotion/transitions';
import { fade } from '@remotion/transitions/fade';
import { type Chapter, ChapterCard } from './components/ChapterCard';
import { PortraitOverlay } from './components/PortraitOverlay';
import { useIsPortrait } from './lib/format';
import { FPS } from './lib/motion';
import voiceover from './voiceover.json';

export type Scene = { id: string; component: ComponentType; durationInFrames: number; chapter?: Chapter };

const TRANSITION_FRAMES = 15;
const CHAPTER_CARD_FRAMES = 66;
const VOICE_LEAD_FRAMES = 10;
const VOICE_TAIL_FRAMES = 14;

const voiceClips: Record<string, { seconds: number }> = voiceover;

/**
 * A segment of the series: a chapter card or a scene, with the frame it
 * starts on, the voice clip (if any) that starts with it, and, for a
 * scene, the chapter it belongs to.
 */
export type Segment = {
    key: string;
    start: number;
    durationInFrames: number;
    render: () => ReactNode;
    voice?: string;
    chapter?: Chapter;
};

/**
 * Lays out chapter cards and scenes back to back, overlapping each by the
 * crossfade. A narrated scene is held on its last frame when its voice
 * clip (which starts on its chapter card) would otherwise be cut off.
 */
const layout = (scenes: Scene[]): Segment[] => {
    const chapters = scenes.flatMap((scene) => (scene.chapter ? [scene.chapter] : []));
    const segments: Segment[] = [];
    let start = 0;

    const push = (segment: Omit<Segment, 'start'>) => {
        segments.push({ ...segment, start });
        start += segment.durationInFrames - TRANSITION_FRAMES;
    };

    for (const scene of scenes) {
        const voiceFrames = voiceClips[scene.id] ? Math.ceil(voiceClips[scene.id].seconds * FPS) : 0;
        const SceneComponent = scene.component;
        const chapter = scene.chapter;

        if (chapter) {
            push({
                key: `${scene.id}-chapter`,
                durationInFrames: CHAPTER_CARD_FRAMES,
                render: () => <ChapterCard chapters={chapters} index={chapters.indexOf(chapter)} />,
                voice: voiceFrames ? scene.id : undefined,
            });
        }

        const voiceAlreadyPlayed = chapter ? CHAPTER_CARD_FRAMES - TRANSITION_FRAMES : 0;
        const neededForVoice = voiceFrames
            ? VOICE_LEAD_FRAMES + voiceFrames + VOICE_TAIL_FRAMES + TRANSITION_FRAMES - voiceAlreadyPlayed
            : 0;

        push({
            key: scene.id,
            durationInFrames: Math.max(scene.durationInFrames, neededForVoice),
            render: () => <SceneComponent />,
            voice: voiceFrames && !chapter ? scene.id : undefined,
            chapter,
        });
    }

    return segments;
};

export const seriesDuration = (scenes: Scene[]): number => {
    const last = layout(scenes).at(-1);

    return last ? last.start + last.durationInFrames : 0;
};

/**
 * Plays scenes back to back with a short crossfade between each. A scene
 * with a chapter is preceded by a full-frame chapter card; chapters are
 * numbered in order across the whole series. Narration from
 * public/voiceover plays over the top when it has been generated; in
 * portrait, the chapter and captions of that narration are added too.
 */
export const SceneSeries = ({ scenes }: { scenes: Scene[] }) => {
    const segments = layout(scenes);
    const portrait = useIsPortrait();

    return (
        <AbsoluteFill>
            <TransitionSeries>
                {segments.map((segment, index) => (
                    <Fragment key={segment.key}>
                        {index > 0 && (
                            <TransitionSeries.Transition
                                presentation={fade()}
                                timing={linearTiming({ durationInFrames: TRANSITION_FRAMES })}
                            />
                        )}
                        <TransitionSeries.Sequence durationInFrames={segment.durationInFrames}>
                            {segment.render()}
                        </TransitionSeries.Sequence>
                    </Fragment>
                ))}
            </TransitionSeries>
            {segments.map(
                (segment) =>
                    segment.voice && (
                        <Sequence key={`${segment.key}-voice`} from={segment.start + VOICE_LEAD_FRAMES} layout="none">
                            <Html5Audio src={staticFile(`voiceover/${segment.voice}.mp3`)} />
                        </Sequence>
                    ),
            )}
            {portrait && <PortraitOverlay segments={segments} voiceLeadFrames={VOICE_LEAD_FRAMES} />}
        </AbsoluteFill>
    );
};
