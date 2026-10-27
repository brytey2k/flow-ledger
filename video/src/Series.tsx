import type { ComponentType } from 'react';
import { Fragment } from 'react';
import { linearTiming, TransitionSeries } from '@remotion/transitions';
import { fade } from '@remotion/transitions/fade';
import { type Chapter, ChapterCard } from './components/ChapterCard';

export type Scene = { id: string; component: ComponentType; durationInFrames: number; chapter?: Chapter };

const TRANSITION_FRAMES = 15;
const CHAPTER_CARD_FRAMES = 66;

const chapterCount = (scenes: Scene[]): number => scenes.filter((scene) => scene.chapter).length;

/**
 * Length of a series, including the chapter card played before each
 * chapter scene and the crossfades between everything.
 */
export const seriesDuration = (scenes: Scene[]): number => {
    const segments = scenes.length + chapterCount(scenes);
    const frames = scenes.reduce((total, scene) => total + scene.durationInFrames, 0) + chapterCount(scenes) * CHAPTER_CARD_FRAMES;

    return frames - TRANSITION_FRAMES * (segments - 1);
};

/**
 * Plays scenes back to back with a short crossfade between each. A scene
 * with a chapter is preceded by a full-frame chapter card; chapters are
 * numbered in order across the whole series.
 */
export const SceneSeries = ({ scenes }: { scenes: Scene[] }) => {
    const chapters = scenes.flatMap((scene) => (scene.chapter ? [scene.chapter] : []));
    const transition = <TransitionSeries.Transition presentation={fade()} timing={linearTiming({ durationInFrames: TRANSITION_FRAMES })} />;

    return (
        <TransitionSeries>
            {scenes.map(({ id, component: SceneComponent, durationInFrames, chapter }, index) => (
                <Fragment key={id}>
                    {index > 0 && transition}
                    {chapter && (
                        <>
                            <TransitionSeries.Sequence durationInFrames={CHAPTER_CARD_FRAMES}>
                                <ChapterCard chapters={chapters} index={chapters.indexOf(chapter)} />
                            </TransitionSeries.Sequence>
                            {transition}
                        </>
                    )}
                    <TransitionSeries.Sequence durationInFrames={durationInFrames}>
                        <SceneComponent />
                    </TransitionSeries.Sequence>
                </Fragment>
            ))}
        </TransitionSeries>
    );
};
