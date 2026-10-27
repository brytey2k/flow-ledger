/**
 * Voice-over script, keyed by scene id. Each line covers its chapter card
 * and scene, written for roughly 140 words per minute. Amounts are spelled
 * the way they should be spoken. The Promo lines voice the 30-second promo,
 * one per beat.
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
        "Add the stages in order. First, approval by the requester's head of department, limited to their own department. Then finance approval. Then disbursement, where the funds are released.",
    SetupOutro:
        'Advances and expenses follow the same path: requester, head of department, finance, then disbursement. A retirement ends with a top-up, a refund, or a nil balance.',
    'Step1-CreateAdvance':
        'Now the workflow is live. Ama Mensah needs funds for a field visit to Tamale. She raises an advance, adds each line item, and FlowLedger totals it in Ghana cedis.',
    'Step2-SubmitRequest':
        'One click submits it for approval. The request moves straight into the workflow. No emails, no chasing signatures.',
    'Step3-ApproveRequest':
        'Kwame, her head of department, finds it waiting in his approvals inbox. He reviews the details, adds a comment, and approves. It then moves on to finance and disbursement on its own.',
    'Step4-DisburseAdvance':
        'Once it is fully approved, Efua pays out the funds and records the payment voucher number. FlowLedger makes sure no one pays out a request they approved themselves.',
    'Step5-RetireAdvance':
        'After the trip, Ama retires the advance. She records what she actually spent, with receipts, and FlowLedger works out the difference. Here, one hundred and seventy cedis to refund.',
    'Reports1-Cfo':
        'For the CFO, the numbers are always current: spending month by month, advances that are still unaccounted for, and the cash available in every branch.',
    'Reports2-It': 'And for IT and audit: approval times measured against your target, and a timestamped trail of every decision.',
    Outro: 'FlowLedger. Every advance approved, paid, and accounted for.',
    'Promo1-Hook': 'Still chasing signatures for staff advances?',
    'Promo2-Reveal': 'Meet FlowLedger.',
    'Promo3-Request': 'Your staff request funds in seconds, right in cedis.',
    'Promo4-Approve': 'Approvals follow the stages your organisation sets, and can be done in under a day.',
    'Promo5-Disburse': 'Pay out, and every payment lands in your cashbook.',
    'Promo6-Retire': 'Retirements work out the refund for you. Every cedi accounted for.',
    'Promo7-Reports': 'And live reports with a full audit trail.',
    'Promo8-Cta': 'FlowLedger. Call or WhatsApp us today to get started.',
};
