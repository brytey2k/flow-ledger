/**
 * Demo data shown in the advance request videos. Labels mirror lang/en/*.php.
 * Every request type follows the same path: the requester submits, then
 * HOD approval, finance approval and disbursement (or, for a retirement,
 * settlement).
 */
export const tenant = { name: 'Acme Logistics', initials: 'AL', host: 'acme.flowledger.app' };

export const people = {
    staff: { name: 'Ama Mensah', initials: 'AM', role: 'Operations Officer' },
    approver: { name: 'Kwame Boateng', initials: 'KB', role: 'Head of Department' },
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

export const workflowStages = ['HOD Approval', 'Finance Approval', 'Disbursement'];

export const stageApprovers = ['Kwame Boateng', 'Abena Osei', 'Kweku Addo'];

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

export const financeDirector = { name: 'Kofi Asare', initials: 'KA', role: 'Finance Director' };

export const setupRole = 'Disbursement Officer';

export const existingRoles = [
    { name: 'admin', users: 1, permissions: 60, created: 'Oct 01, 2026', time: '8:02 AM' },
    { name: 'Finance Director', users: 1, permissions: 5, created: 'Oct 01, 2026', time: '8:02 AM' },
    { name: 'Finance Officer', users: 2, permissions: 5, created: 'Oct 01, 2026', time: '8:02 AM' },
    { name: 'Head of Department', users: 5, permissions: 5, created: 'Oct 01, 2026', time: '8:02 AM' },
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

export const allRoleNames = ['admin', 'Disbursement Officer', 'Finance Director', 'Finance Officer', 'Head of Department'];

export const invitee = { firstName: 'Efua', lastName: 'Owusu', email: 'efua.owusu@acmelogistics.com', branch: 'Kumasi Branch' };

export const setupTemplate = { name: 'Advance Approval' };

export const setupStages = [
    { name: 'HOD Approval', role: 'Head of Department', scope: 'Department' },
    { name: 'Finance Approval', role: 'Finance Officer', scope: 'Branch' },
    { name: 'Disbursement', role: 'Disbursement Officer', scope: 'Branch' },
];

/**
 * The three request workflows summarised at the end of the setup: each
 * starts with the requester and ends with the money moving.
 */
export const requestWorkflows = [
    { name: 'Advance Approval', type: 'Advance', badge: 'sgh-badge-primary', last: 'Disbursement' },
    { name: 'Expense Approval', type: 'Expense', badge: 'sgh-badge-success', last: 'Disbursement' },
    { name: 'Retirement Approval', type: 'Retirement', badge: 'sgh-badge-warning', last: 'Disbursement, refund or nil balance' },
];

/**
 * Organisation structure; departments and cost codes mirror the defaults
 * NewTenantSetupService creates.
 */
export const branches = [
    { name: 'Head Office', code: 'HQ', level: 'Head Office', parent: '—' },
    { name: 'Kumasi Branch', code: 'KSI', level: 'Branch', parent: 'Head Office' },
    { name: 'Tamale Branch', code: 'TML', level: 'Branch', parent: 'Head Office' },
    { name: 'Takoradi Branch', code: 'TKD', level: 'Branch', parent: 'Head Office' },
];

export const departments = ['Finance & Accounts', 'Administration', 'Operations', 'Human Resources', 'Procurement'];

export const costCodes = [
    { code: 'FIN-001', name: 'General Finance & Accounts', department: 'Finance & Accounts' },
    { code: 'ADM-001', name: 'General Administration', department: 'Administration' },
    { code: 'OPS-001', name: 'General Operations', department: 'Operations' },
    { code: 'HR-001', name: 'General Human Resources', department: 'Human Resources' },
    { code: 'PRC-001', name: 'General Procurement', department: 'Procurement' },
];

export const currencies = [
    { name: 'Ghana Cedi', code: 'GHS', symbol: 'GH₵' },
    { name: 'US Dollar', code: 'USD', symbol: '$' },
];

/**
 * Report figures for the CFO and IT/audit chapters. Request #1042 and its
 * approvers reappear so the reports read as the same tenant.
 */
export const spendTrend = [
    { month: 'Jan', total: 48200 },
    { month: 'Feb', total: 52750 },
    { month: 'Mar', total: 61300 },
    { month: 'Apr', total: 57900 },
    { month: 'May', total: 66400 },
    { month: 'Jun', total: 71250 },
    { month: 'Jul', total: 69800 },
    { month: 'Aug', total: 74100 },
    { month: 'Sep', total: 80600 },
    { month: 'Oct', total: 86950 },
];

export const agingBuckets = [
    { label: '0–30 days', badge: 'sgh-badge-success', count: 14, total: 38450 },
    { label: '31–60 days', badge: 'sgh-badge-warning', count: 5, total: 12900 },
    { label: '61+ days', badge: 'sgh-badge-danger', count: 2, total: 6300 },
];

export const outstandingAdvances = [
    {
        id: 1042,
        staff: 'Ama Mensah',
        branch: 'Kumasi Branch',
        department: 'Operations',
        amount: 2950,
        disbursedOn: 'Oct 28, 2026',
        days: 4,
        bucket: 0,
    },
    {
        id: 1036,
        staff: 'Kojo Antwi',
        branch: 'Head Office',
        department: 'Administration',
        amount: 1800,
        disbursedOn: 'Oct 14, 2026',
        days: 18,
        bucket: 0,
    },
    {
        id: 987,
        staff: 'Yaw Darko',
        branch: 'Tamale Branch',
        department: 'Procurement',
        amount: 4200,
        disbursedOn: 'Sep 22, 2026',
        days: 40,
        bucket: 1,
    },
    {
        id: 911,
        staff: 'Akosua Mensa',
        branch: 'Takoradi Branch',
        department: 'Administration',
        amount: 3100,
        disbursedOn: 'Aug 18, 2026',
        days: 75,
        bucket: 2,
    },
];

export const cashbooks = [
    { branch: 'Head Office', balance: 184250, awaiting: 12400, receipts: 95000, payments: 64320, entries: 38 },
    { branch: 'Kumasi Branch', balance: 42600, awaiting: 5800, receipts: 30000, payments: 21450, entries: 17 },
    { branch: 'Tamale Branch', balance: 18900, awaiting: 4200, receipts: 15000, payments: 12780, entries: 11 },
];

export const sla = { complianceRate: 92, slaDays: 3, averageDays: 1.6, total: 148, compliant: 136 };

export const slaRows = [
    { id: 1042, staff: 'Ama Mensah', type: 'Advance', submitted: 'Oct 27, 2026', approved: 'Oct 27, 2026', days: 0 },
    { id: 1040, staff: 'Kojo Antwi', type: 'Advance', submitted: 'Oct 24, 2026', approved: 'Oct 26, 2026', days: 2 },
    { id: 1038, staff: 'Yaw Darko', type: 'Expense', submitted: 'Oct 19, 2026', approved: 'Oct 24, 2026', days: 5 },
    { id: 1035, staff: 'Esi Quaye', type: 'Expense', submitted: 'Oct 20, 2026', approved: 'Oct 21, 2026', days: 1 },
];

export const auditActions = [
    { request: 1042, stage: 'Disbursement', by: 'Kweku Addo', action: 'approve', comment: '—', at: 'Oct 27, 2026 2:05 PM' },
    { request: 1042, stage: 'Finance Approval', by: 'Abena Osei', action: 'approve', comment: '—', at: 'Oct 27, 2026 12:40 PM' },
    {
        request: 1042,
        stage: 'HOD Approval',
        by: 'Kwame Boateng',
        action: 'approve',
        comment: 'Budget confirmed against the Q4 operations plan.',
        at: 'Oct 27, 2026 11:02 AM',
    },
    {
        request: 1038,
        stage: 'HOD Approval',
        by: 'Kwame Boateng',
        action: 'send_back',
        comment: 'Attach the venue quote before resubmitting.',
        at: 'Oct 21, 2026 3:18 PM',
    },
    {
        request: 1031,
        stage: 'Finance Approval',
        by: 'Abena Osei',
        action: 'reject',
        comment: 'Duplicate of #1027.',
        at: 'Oct 19, 2026 10:47 AM',
    },
];
