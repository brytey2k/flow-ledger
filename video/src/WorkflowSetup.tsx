import { type Scene, SceneSeries, seriesDuration } from './Series';
import { ADD_STAGES_DURATION, AddStages } from './scenes/setup/AddStages';
import { CREATE_ROLE_DURATION, CreateRole } from './scenes/setup/CreateRole';
import { CREATE_TEMPLATE_DURATION, CreateTemplate } from './scenes/setup/CreateTemplate';
import { GRANT_PERMISSIONS_DURATION, GrantPermissions } from './scenes/setup/GrantPermissions';
import { INVITE_USER_DURATION, InviteUser } from './scenes/setup/InviteUser';
import { SETUP_INTRO_DURATION, SetupIntro } from './scenes/setup/SetupIntro';
import { SETUP_OUTRO_DURATION, SetupOutro } from './scenes/setup/SetupOutro';

export const setupScenes: Scene[] = [
    { id: 'SetupIntro', component: SetupIntro, durationInFrames: SETUP_INTRO_DURATION },
    { id: 'Setup1-CreateRole', component: CreateRole, durationInFrames: CREATE_ROLE_DURATION },
    { id: 'Setup2-GrantPermissions', component: GrantPermissions, durationInFrames: GRANT_PERMISSIONS_DURATION },
    { id: 'Setup3-InviteUser', component: InviteUser, durationInFrames: INVITE_USER_DURATION },
    { id: 'Setup4-CreateTemplate', component: CreateTemplate, durationInFrames: CREATE_TEMPLATE_DURATION },
    { id: 'Setup5-AddStages', component: AddStages, durationInFrames: ADD_STAGES_DURATION },
    { id: 'SetupOutro', component: SetupOutro, durationInFrames: SETUP_OUTRO_DURATION },
];

export const WORKFLOW_SETUP_DURATION = seriesDuration(setupScenes);

/**
 * The prequel: create a role, grant its permissions, invite someone into
 * it, then build the advance approval workflow from roles.
 */
export const WorkflowSetup = () => <SceneSeries scenes={setupScenes} />;
