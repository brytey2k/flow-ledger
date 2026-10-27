import { advanceChapterScenes, outroScene } from './AdvanceRequestFlow';
import { chapters } from './chapters';
import { type Scene, SceneSeries, seriesDuration } from './Series';
import { TitleCard } from './scenes/Intro';
import { CFO_REPORTS_DURATION, CfoReports } from './scenes/reports/CfoReports';
import { IT_REPORTS_DURATION, ItReports } from './scenes/reports/ItReports';
import { setupChapterScenes } from './WorkflowSetup';

const WalkthroughIntro = () => (
    <TitleCard
        title="FlowLedger, end to end"
        subtitle="Set up approvals, run an advance, and read the numbers."
        steps={['Organisation', 'People & access', 'Approval workflow', 'Advance request', 'Reports']}
    />
);

export const reportScenes: Scene[] = [
    { id: 'Reports1-Cfo', component: CfoReports, durationInFrames: CFO_REPORTS_DURATION, chapter: chapters['Reports1-Cfo'] },
    { id: 'Reports2-It', component: ItReports, durationInFrames: IT_REPORTS_DURATION, chapter: chapters['Reports2-It'] },
];

const walkthroughScenes: Scene[] = [
    { id: 'WalkthroughIntro', component: WalkthroughIntro, durationInFrames: 120 },
    ...setupChapterScenes,
    ...advanceChapterScenes,
    ...reportScenes,
    outroScene,
];

export const FULL_WALKTHROUGH_DURATION = seriesDuration(walkthroughScenes);

/**
 * One continuous story: organisation and access setup, the approval
 * workflow, an advance from request to retirement, then the reports.
 */
export const FullWalkthrough = () => <SceneSeries scenes={walkthroughScenes} />;
