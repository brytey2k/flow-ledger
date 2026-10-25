import type { ComponentType } from 'react';
import { Fragment } from 'react';
import { linearTiming, TransitionSeries } from '@remotion/transitions';
import { fade } from '@remotion/transitions/fade';

export type Scene = { id: string; component: ComponentType; durationInFrames: number };

const TRANSITION_FRAMES = 15;

export const seriesDuration = (scenes: Scene[]): number =>
    scenes.reduce((total, scene) => total + scene.durationInFrames, 0) - TRANSITION_FRAMES * (scenes.length - 1);

/**
 * Plays scenes back to back with a short crossfade between each.
 */
export const SceneSeries = ({ scenes }: { scenes: Scene[] }) => (
    <TransitionSeries>
        {scenes.map(({ id, component: SceneComponent, durationInFrames }, index) => (
            <Fragment key={id}>
                {index > 0 && (
                    <TransitionSeries.Transition presentation={fade()} timing={linearTiming({ durationInFrames: TRANSITION_FRAMES })} />
                )}
                <TransitionSeries.Sequence durationInFrames={durationInFrames}>
                    <SceneComponent />
                </TransitionSeries.Sequence>
            </Fragment>
        ))}
    </TransitionSeries>
);
