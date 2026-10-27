/**
 * Voice-over script, keyed by scene id. Each line covers its chapter card
 * and scene, written for roughly 140 words per minute. Amounts are spelled
 * the way they should be spoken.
 */
export const narration: Record<string, string> = {
    WalkthroughIntro: 'Welcome to FlowLedger. In the next two minutes, we will set up approvals, run an advance, and read the numbers.',
    'Setup0-Organisation':
        'First, your organisation. Add your branches, departments, cost codes and currencies. Every request in FlowLedger is filed against this structure.',
    'Setup1-CreateRole':
        'Next, roles. A role describes what someone does, like Disbursement Officer. Approval stages are assigned to roles, not to individual people.',
    'Setup2-GrantPermissions':
        'Now give the role its permissions. Tick exactly what it may do: approve requests, disburse funds, settle retirements. Everyone holding the role inherits them.',
    'Setup3-InviteUser':
        'Then invite your people. Here, Efua Owusu joins the Kumasi branch as a Disbursement Officer. Her approvals inbox follows from the roles she holds.',
    'Setup4-CreateTemplate':
        'With your people in place, create the workflow. One template per request type. Leave the branch blank, and it covers every branch.',
    'Setup5-AddStages':
        'Add the approval stages in order: Finance Officer, Finance Manager, Finance Director, then Disbursement Officer. A stage can be skipped for small amounts, or limited to approvers in the same branch.',
    'Step1-CreateAdvance':
        'Now the workflow is live. Ama Mensah needs funds for a field visit to Tamale. She raises an advance, adds each line item, and FlowLedger totals it in Ghana cedis.',
    'Step2-SubmitRequest': 'One click submits it for approval. The request moves straight into the workflow. No emails, no chasing signatures.',
    'Step3-ApproveRequest':
        'Kwame, the Finance Officer, finds it waiting in his approvals inbox. He reviews the details, adds a comment, and approves. The request then moves through each stage on its own.',
    'Step4-DisburseAdvance':
        'Once it is fully approved, Efua pays out the funds and records the mobile money reference. FlowLedger makes sure no one pays out a request they approved themselves.',
    'Step5-RetireAdvance':
        'After the trip, Ama retires the advance. She records what she actually spent, with receipts, and FlowLedger works out the difference. Here, one hundred and seventy cedis to refund.',
    'Reports1-Cfo':
        'For the CFO, the numbers are always current: spending month by month, advances that are still unaccounted for, and the cash available in every branch.',
    'Reports2-It': 'And for IT and audit: approval times measured against your target, and a timestamped trail of every decision.',
    Outro: 'FlowLedger. Every advance approved, paid, and accounted for.',
};
