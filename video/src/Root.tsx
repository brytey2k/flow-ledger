import './style.css';
import { Composition, Folder } from 'remotion';
import { ADVANCE_REQUEST_FLOW_DURATION, AdvanceRequestFlow, scenes } from './AdvanceRequestFlow';
import { loadFonts } from './fonts';
import { FPS } from './lib/motion';

loadFonts();

const size = { fps: FPS, width: 1920, height: 1080 };

export const RemotionRoot = () => (
    <>
        <Composition id="AdvanceRequestFlow" component={AdvanceRequestFlow} durationInFrames={ADVANCE_REQUEST_FLOW_DURATION} {...size} />
        <Folder name="Scenes">
            {scenes.map((scene) => (
                <Composition key={scene.id} id={scene.id} component={scene.component} durationInFrames={scene.durationInFrames} {...size} />
            ))}
        </Folder>
    </>
);
