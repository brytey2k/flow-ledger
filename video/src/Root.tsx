import './style.css';
import { Composition, Folder } from 'remotion';
import { ADVANCE_REQUEST_FLOW_DURATION, AdvanceRequestFlow, advanceScenes } from './AdvanceRequestFlow';
import { loadFonts } from './fonts';
import { FULL_WALKTHROUGH_DURATION, FullWalkthrough, reportScenes } from './FullWalkthrough';
import { LANDSCAPE, PORTRAIT } from './lib/format';
import { FPS } from './lib/motion';
import type { Scene } from './Series';
import { setupScenes, WORKFLOW_SETUP_DURATION, WorkflowSetup } from './WorkflowSetup';

loadFonts();

const size = { fps: FPS, ...LANDSCAPE };
const portraitSize = { fps: FPS, ...PORTRAIT };

const SceneFolder = ({ name, scenes }: { name: string; scenes: Scene[] }) => (
    <Folder name={name}>
        {scenes.map((scene) => (
            <Composition key={scene.id} id={scene.id} component={scene.component} durationInFrames={scene.durationInFrames} {...size} />
        ))}
    </Folder>
);

export const RemotionRoot = () => (
    <>
        <Composition id="FullWalkthrough" component={FullWalkthrough} durationInFrames={FULL_WALKTHROUGH_DURATION} {...size} />
        <Composition id="WorkflowSetup" component={WorkflowSetup} durationInFrames={WORKFLOW_SETUP_DURATION} {...size} />
        <Composition id="AdvanceRequestFlow" component={AdvanceRequestFlow} durationInFrames={ADVANCE_REQUEST_FLOW_DURATION} {...size} />
        <Folder name="Portrait">
            <Composition
                id="FullWalkthrough-Portrait"
                component={FullWalkthrough}
                durationInFrames={FULL_WALKTHROUGH_DURATION}
                {...portraitSize}
            />
            <Composition
                id="WorkflowSetup-Portrait"
                component={WorkflowSetup}
                durationInFrames={WORKFLOW_SETUP_DURATION}
                {...portraitSize}
            />
            <Composition
                id="AdvanceRequestFlow-Portrait"
                component={AdvanceRequestFlow}
                durationInFrames={ADVANCE_REQUEST_FLOW_DURATION}
                {...portraitSize}
            />
        </Folder>
        <SceneFolder name="SetupScenes" scenes={setupScenes} />
        <SceneFolder name="AdvanceScenes" scenes={advanceScenes} />
        <SceneFolder name="ReportScenes" scenes={reportScenes} />
    </>
);
