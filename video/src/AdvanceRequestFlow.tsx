import { chapters } from './chapters';
import { type Scene, SceneSeries, seriesDuration } from './Series';
import { APPROVE_REQUEST_DURATION, ApproveRequest } from './scenes/ApproveRequest';
import { CREATE_ADVANCE_DURATION, CreateAdvance } from './scenes/CreateAdvance';
import { DISBURSE_ADVANCE_DURATION, DisburseAdvance } from './scenes/DisburseAdvance';
import { INTRO_DURATION, Intro } from './scenes/Intro';
import { OUTRO_DURATION, Outro } from './scenes/Outro';
import { RETIRE_ADVANCE_DURATION, RetireAdvance } from './scenes/RetireAdvance';
import { SUBMIT_REQUEST_DURATION, SubmitRequest } from './scenes/SubmitRequest';

const chapterScene = (scene: Omit<Scene, 'chapter'>): Scene => ({ ...scene, chapter: chapters[scene.id] });

export const advanceChapterScenes: Scene[] = [
    chapterScene({ id: 'Step1-CreateAdvance', component: CreateAdvance, durationInFrames: CREATE_ADVANCE_DURATION }),
    chapterScene({ id: 'Step2-SubmitRequest', component: SubmitRequest, durationInFrames: SUBMIT_REQUEST_DURATION }),
    chapterScene({ id: 'Step3-ApproveRequest', component: ApproveRequest, durationInFrames: APPROVE_REQUEST_DURATION }),
    chapterScene({ id: 'Step4-DisburseAdvance', component: DisburseAdvance, durationInFrames: DISBURSE_ADVANCE_DURATION }),
    chapterScene({ id: 'Step5-RetireAdvance', component: RetireAdvance, durationInFrames: RETIRE_ADVANCE_DURATION }),
];

export const outroScene: Scene = { id: 'Outro', component: Outro, durationInFrames: OUTRO_DURATION };

export const advanceScenes: Scene[] = [
    { id: 'Intro', component: Intro, durationInFrames: INTRO_DURATION },
    ...advanceChapterScenes,
    outroScene,
];

export const ADVANCE_REQUEST_FLOW_DURATION = seriesDuration(advanceScenes);

/**
 * The full advance lifecycle: raise → submit → approve → disburse → retire.
 */
export const AdvanceRequestFlow = () => <SceneSeries scenes={advanceScenes} />;
