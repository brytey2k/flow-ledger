import type { Chapter } from './components/ChapterCard';

const ORGANISATION = 'Organisation';
const ACCESS = 'People & access';
const WORKFLOW = 'Approval workflow';
const ADVANCE = 'Advance request';
const REPORTS = 'Reports';

/**
 * Chapter card copy, keyed by scene id.
 */
export const chapters: Record<string, Chapter> = {
    'Setup0-Organisation': {
        part: ORGANISATION,
        title: 'Set up your organisation',
        body: 'Branches, departments, cost codes and currencies: the structure every request is filed against.',
    },
    'Setup1-CreateRole': {
        part: ACCESS,
        title: 'Create a role',
        body: 'Roles describe what someone does. Approval stages are assigned to roles, not people.',
    },
    'Setup2-GrantPermissions': {
        part: ACCESS,
        title: 'Grant permissions',
        body: 'Pick what the role can do. Everyone holding the role inherits these permissions.',
    },
    'Setup3-InviteUser': {
        part: ACCESS,
        title: 'Invite people into roles',
        body: 'Give each person the roles they hold. Their approvals inbox follows from it.',
    },
    'Setup4-CreateTemplate': {
        part: WORKFLOW,
        title: 'Create the workflow',
        body: 'One template per request type. Leave the branch blank to cover every branch.',
    },
    'Setup5-AddStages': {
        part: WORKFLOW,
        title: 'Add approval stages',
        body: 'HOD approval, finance approval, then disbursement. Each stage names the roles that approve it.',
    },
    'Step1-CreateAdvance': {
        part: ADVANCE,
        title: 'Raise an advance',
        body: 'Staff request funds before they spend, line by line, in their branch currency.',
    },
    'Step2-SubmitRequest': {
        part: ADVANCE,
        title: 'Submit for approval',
        body: "One click starts the tenant's approval workflow — no emails, no chasing.",
    },
    'Step3-ApproveRequest': {
        part: ADVANCE,
        title: 'Approve, stage by stage',
        body: 'Each approver reviews in their inbox and the request moves on by itself.',
    },
    'Step4-DisburseAdvance': {
        part: ADVANCE,
        title: 'Disburse the funds',
        body: 'Finance pays out and records the reference. Approvers cannot disburse their own approvals.',
    },
    'Step5-RetireAdvance': {
        part: ADVANCE,
        title: 'Retire the advance',
        body: 'Staff account for every cedi spent. FlowLedger works out the refund or top-up.',
    },
    'Reports1-Cfo': {
        part: REPORTS,
        title: 'What the CFO sees',
        body: 'Monthly spend, advances still unaccounted for, and cash available in every branch.',
    },
    'Reports2-It': {
        part: REPORTS,
        title: 'What IT and audit see',
        body: 'Approval SLAs, and a timestamped trail of every approval decision.',
    },
};
