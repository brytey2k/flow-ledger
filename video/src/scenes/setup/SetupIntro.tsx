import { TitleCard } from '../Intro';

export const SETUP_INTRO_DURATION = 120;

export const SetupIntro = () => (
    <TitleCard
        title="Setting up approvals"
        subtitle="Roles, permissions and workflows, configured once per tenant."
        steps={['Role', 'Permissions', 'People', 'Workflow', 'Stages']}
    />
);
