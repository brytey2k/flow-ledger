/**
 * Demo data shown in the advance request videos. Labels mirror lang/en/*.php
 * and the stage names mirror NewTenantSetupService's default workflow.
 */
export const tenant = { name: 'Acme Logistics', initials: 'AL', host: 'acme.flowledger.app' };

export const people = {
    staff: { name: 'Ama Mensah', initials: 'AM', role: 'Operations Officer' },
    approver: { name: 'Kwame Boateng', initials: 'KB', role: 'Finance Officer' },
    disburser: { name: 'Efua Owusu', initials: 'EO', role: 'Disbursement Officer' },
} as const;

export type Person = { name: string; initials: string; role: string };

export const advance = {
    id: 1042,
    department: 'Operations',
    branch: 'Kumasi Branch',
    currency: { shortName: 'GHS', name: 'Ghana Cedi', symbol: 'GH₵' },
    notes: 'Field visit to the Tamale depot for the Q4 stock audit.',
    items: [
        { description: 'Transport to Tamale', amount: 850 },
        { description: 'Accommodation (3 nights)', amount: 1500 },
        { description: 'Per diem', amount: 600 },
    ],
    total: 2950,
    createdAt: 'Oct 27, 2026 at 9:14 AM',
    submittedAt: 'Oct 27, 2026 9:16 AM',
    submittedOn: 'Oct 27, 2026',
    approvedAt: 'Oct 27, 2026 2:05 PM',
    disbursedAt: 'Oct 28, 2026 10:42 AM',
    disbursementReference: 'MOMO-48213',
    approvalComment: 'Budget confirmed against the Q4 operations plan.',
};

export const workflowStages = ['Finance Officer', 'Finance Manager', 'Finance Director', 'Disbursement Officer'];

export const stageApprovers = ['Kwame Boateng', 'Abena Osei', 'Kofi Asare', 'Efua Owusu'];

export const retirement = {
    items: [
        { description: 'Bus fare, Kumasi – Tamale return', costCode: 'OPS-001 — General Operations', amount: 780, receipt: 'VIP-22817' },
        { description: 'Hotel, 3 nights', costCode: 'OPS-001 — General Operations', amount: 1450, receipt: 'INV-0937' },
        { description: 'Meals & incidentals', costCode: 'OPS-001 — General Operations', amount: 550, receipt: '' },
    ],
    total: 2780,
    difference: 170,
    differenceLabel: 'Staff spent less — refund required.',
};

export type RequestStatus = 'draft' | 'in_workflow' | 'approved' | 'disbursed' | 'retired';

export const statusLabels: Record<RequestStatus, string> = {
    draft: 'Draft',
    in_workflow: 'In Workflow',
    approved: 'Approved',
    disbursed: 'Disbursed',
    retired: 'Retired',
};

/**
 * Same mapping as the $statusColors array in payment-requests/show.blade.php.
 */
export const statusBadgeClasses: Record<RequestStatus, string> = {
    draft: 'sgh-badge-outline',
    in_workflow: 'sgh-badge-primary',
    approved: 'sgh-badge-success',
    disbursed: 'sgh-badge-info',
    retired: 'sgh-badge-neutral',
};

/**
 * Setup prequel: an admin creates the Disbursement Officer role, grants it
 * the same permissions NewTenantSetupService seeds, invites Efua into it
 * and builds the Advance Approval workflow used in the advance video.
 */
export const admin = { name: 'Nana Adjei', initials: 'NA', role: 'Administrator' };

export const setupRole = 'Disbursement Officer';

export const existingRoles = [
    { name: 'admin', users: 1, permissions: 60, created: 'Oct 01, 2026', time: '8:02 AM' },
    { name: 'Finance Director', users: 1, permissions: 5, created: 'Oct 01, 2026', time: '8:02 AM' },
    { name: 'Finance Manager', users: 1, permissions: 5, created: 'Oct 01, 2026', time: '8:02 AM' },
    { name: 'Finance Officer', users: 2, permissions: 5, created: 'Oct 01, 2026', time: '8:02 AM' },
];

/**
 * Permission categories shown on the role permissions page (a subset of
 * PermissionCategory), with the permissions this role is granted.
 */
export const permissionGroups = [
    {
        category: 'Payment Requests',
        permissions: ['Access Payment Requests', 'Create Payment Request', 'Edit Payment Request', 'Delete Payment Request'],
        granted: ['Access Payment Requests', 'Create Payment Request'],
    },
    {
        category: 'Retirement Requests',
        permissions: ['Access Retirement Requests', 'Create Retirement Request', 'Edit Retirement Request'],
        granted: ['Access Retirement Requests'],
    },
    {
        category: 'Workflow & Approvals',
        permissions: [
            'Access Workflow Templates',
            'Create Workflow Template',
            'Edit Workflow Template',
            'Delete Workflow Template',
            'Approve Requests',
            'Disburse Requests',
            'Settle Retirements',
        ],
        granted: ['Approve Requests', 'Disburse Requests', 'Settle Retirements'],
    },
    {
        category: 'Cashbook',
        permissions: ['Access Cashbook', 'Create Cashbook Entry', 'Delete Cashbook Entry'],
        granted: [],
    },
];

export const allRoleNames = ['admin', 'Disbursement Officer', 'Finance Director', 'Finance Manager', 'Finance Officer'];

export const invitee = { firstName: 'Efua', lastName: 'Owusu', email: 'efua.owusu@acmelogistics.com', branch: 'Kumasi Branch' };

export const setupTemplate = { name: 'Advance Approval' };

export const setupStages = [
    { name: 'Finance Officer', role: 'Finance Officer', skipBelow: undefined },
    { name: 'Finance Manager', role: 'Finance Manager', skipBelow: undefined },
    { name: 'Finance Director', role: 'Finance Director', skipBelow: 1000 },
    { name: 'Disbursement Officer', role: 'Disbursement Officer', skipBelow: undefined },
];
