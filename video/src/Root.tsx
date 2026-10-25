import './style.css';
import { Composition, Folder } from 'remotion';
import { ADVANCE_REQUEST_FLOW_DURATION, AdvanceRequestFlow, advanceScenes } from './AdvanceRequestFlow';
import { loadFonts } from './fonts';
import { FPS } from './lib/motion';
import { SceneSeries, seriesDuration } from './Series';
import { setupScenes, WORKFLOW_SETUP_DURATION, WorkflowSetup } from './WorkflowSetup';

loadFonts();

const size = { fps: FPS, width: 1920, height: 1080 };
const walkthroughScenes = [...setupScenes, ...advanceScenes];

const FullWalkthrough = () => <SceneSeries scenes={walkthroughScenes} />;

export const RemotionRoot = () => (
    <>
        <Composition id="WorkflowSetup" component={WorkflowSetup} durationInFrames={WORKFLOW_SETUP_DURATION} {...size} />
        <Composition id="AdvanceRequestFlow" component={AdvanceRequestFlow} durationInFrames={ADVANCE_REQUEST_FLOW_DURATION} {...size} />
        <Composition id="FullWalkthrough" component={FullWalkthrough} durationInFrames={seriesDuration(walkthroughScenes)} {...size} />
        <Folder name="SetupScenes">
            {setupScenes.map((scene) => (
                <Composition key={scene.id} id={scene.id} component={scene.component} durationInFrames={scene.durationInFrames} {...size} />
            ))}
        </Folder>
        <Folder name="AdvanceScenes">
            {advanceScenes.map((scene) => (
                <Composition key={scene.id} id={scene.id} component={scene.component} durationInFrames={scene.durationInFrames} {...size} />
            ))}
        </Folder>
    </>
);
