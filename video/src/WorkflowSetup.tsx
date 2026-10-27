import { chapters } from './chapters';
import { type Scene, SceneSeries, seriesDuration } from './Series';
import { ADD_STAGES_DURATION, AddStages } from './scenes/setup/AddStages';
import { CREATE_ROLE_DURATION, CreateRole } from './scenes/setup/CreateRole';
import { CREATE_TEMPLATE_DURATION, CreateTemplate } from './scenes/setup/CreateTemplate';
import { GRANT_PERMISSIONS_DURATION, GrantPermissions } from './scenes/setup/GrantPermissions';
import { INVITE_USER_DURATION, InviteUser } from './scenes/setup/InviteUser';
import { ORGANISATION_SETUP_DURATION, OrganisationSetup } from './scenes/setup/OrganisationSetup';
import { SETUP_INTRO_DURATION, SetupIntro } from './scenes/setup/SetupIntro';
import { SETUP_OUTRO_DURATION, SetupOutro } from './scenes/setup/SetupOutro';

const chapterScene = (scene: Omit<Scene, 'chapter'>): Scene => ({ ...scene, chapter: chapters[scene.id] });

export const setupChapterScenes: Scene[] = [
    chapterScene({ id: 'Setup0-Organisation', component: OrganisationSetup, durationInFrames: ORGANISATION_SETUP_DURATION }),
    chapterScene({ id: 'Setup1-CreateRole', component: CreateRole, durationInFrames: CREATE_ROLE_DURATION }),
    chapterScene({ id: 'Setup2-GrantPermissions', component: GrantPermissions, durationInFrames: GRANT_PERMISSIONS_DURATION }),
    chapterScene({ id: 'Setup3-InviteUser', component: InviteUser, durationInFrames: INVITE_USER_DURATION }),
    chapterScene({ id: 'Setup4-CreateTemplate', component: CreateTemplate, durationInFrames: CREATE_TEMPLATE_DURATION }),
    chapterScene({ id: 'Setup5-AddStages', component: AddStages, durationInFrames: ADD_STAGES_DURATION }),
];

export const setupScenes: Scene[] = [
    { id: 'SetupIntro', component: SetupIntro, durationInFrames: SETUP_INTRO_DURATION },
    ...setupChapterScenes,
    { id: 'SetupOutro', component: SetupOutro, durationInFrames: SETUP_OUTRO_DURATION },
];

export const WORKFLOW_SETUP_DURATION = seriesDuration(setupScenes);

/**
 * The prequel: organisation structure, roles, permissions and people, then
 * the advance approval workflow built from those roles.
 */
export const WorkflowSetup = () => <SceneSeries scenes={setupScenes} />;
