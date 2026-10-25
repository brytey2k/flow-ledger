import { linearTiming, TransitionSeries } from '@remotion/transitions';
import { fade } from '@remotion/transitions/fade';
import { Fragment } from 'react';
import { APPROVE_REQUEST_DURATION, ApproveRequest } from './scenes/ApproveRequest';
import { CREATE_ADVANCE_DURATION, CreateAdvance } from './scenes/CreateAdvance';
import { DISBURSE_ADVANCE_DURATION, DisburseAdvance } from './scenes/DisburseAdvance';
import { INTRO_DURATION, Intro } from './scenes/Intro';
import { OUTRO_DURATION, Outro } from './scenes/Outro';
import { RETIRE_ADVANCE_DURATION, RetireAdvance } from './scenes/RetireAdvance';
import { SUBMIT_REQUEST_DURATION, SubmitRequest } from './scenes/SubmitRequest';

const TRANSITION_FRAMES = 15;

export const scenes = [
    { id: 'Intro', component: Intro, durationInFrames: INTRO_DURATION },
    { id: 'Step1-CreateAdvance', component: CreateAdvance, durationInFrames: CREATE_ADVANCE_DURATION },
    { id: 'Step2-SubmitRequest', component: SubmitRequest, durationInFrames: SUBMIT_REQUEST_DURATION },
    { id: 'Step3-ApproveRequest', component: ApproveRequest, durationInFrames: APPROVE_REQUEST_DURATION },
    { id: 'Step4-DisburseAdvance', component: DisburseAdvance, durationInFrames: DISBURSE_ADVANCE_DURATION },
    { id: 'Step5-RetireAdvance', component: RetireAdvance, durationInFrames: RETIRE_ADVANCE_DURATION },
    { id: 'Outro', component: Outro, durationInFrames: OUTRO_DURATION },
];

export const ADVANCE_REQUEST_FLOW_DURATION =
    scenes.reduce((total, scene) => total + scene.durationInFrames, 0) - TRANSITION_FRAMES * (scenes.length - 1);

/**
 * The full advance lifecycle: raise → submit → approve → disburse → retire.
 */
export const AdvanceRequestFlow = () => (
    <TransitionSeries>
        {scenes.map(({ id, component: Scene, durationInFrames }, index) => (
            <Fragment key={id}>
                {index > 0 && (
                    <TransitionSeries.Transition presentation={fade()} timing={linearTiming({ durationInFrames: TRANSITION_FRAMES })} />
                )}
                <TransitionSeries.Sequence durationInFrames={durationInFrames}>
                    <Scene />
                </TransitionSeries.Sequence>
            </Fragment>
        ))}
    </TransitionSeries>
);
