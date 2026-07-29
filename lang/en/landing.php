<?php

declare(strict_types=1);

return [
    'meta' => [
        'title_suffix' => 'Approval workflows finance teams can prove',
        'description' => 'Flow Ledger routes disbursement, payment, and retirement requests through configurable approval workflows, with a full audit trail, multi-tenant roles, and SSO built in.',
    ],

    'nav' => [
        'how_it_works' => 'How it works',
        'security' => 'Security',
        'get_started' => 'Get started',
        'toggle_menu' => 'Toggle menu',
        'language' => 'Language',
    ],

    'hero' => [
        'headline' => 'Every approval, accounted for.',
        'lede' => 'Flow Ledger routes disbursement, payment, and retirement requests through the approval chains your organization already runs — branch by branch, department by department, with a record of every decision.',
        'get_started' => 'Get started',
        'see_how_it_works' => 'See how it works',
        'note' => 'New to Flow Ledger? Ask your administrator for access to your workspace.',
        'figure_alt' => 'Flow Ledger dashboard showing a branch cash balance against its threshold',
        'figure_caption' => 'A live workspace — branch balances, thresholds, and requests in one view.',
    ],

    'steps' => [
        'heading' => 'How a request moves through Flow Ledger.',
        'lede' => 'Four stages, the same ones your finance team already follows — just with a system tracking every handoff.',
        'submit' => [
            'heading' => 'Submit the request',
            'body' => 'Staff raise payment, disbursement, or retirement requests tagged to a cost code, department, and branch — so every request starts with the context finance needs to review it.',
            'alt' => 'Payment requests list showing amounts, branches, and statuses',
        ],
        'route' => [
            'heading' => 'Route it for approval',
            'body' => 'Multi-stage and parallel approval groups move the request through the right people, with per-stage thresholds and approver scopes. Edit a workflow later, and requests already in flight keep running on the version they started on — nothing breaks mid-approval.',
            'alt' => 'Approval workflow configuration showing sequential stages and a parallel final-approval group',
        ],
        'release' => [
            'heading' => 'Release the funds',
            'body' => "Approved disbursements post to the branch cashbook. Cash counts reconcile what's on hand against what's been recorded, branch by branch.",
            'alt' => 'Cashbook view showing cash balances across four branches',
        ],
        'close' => [
            'heading' => 'Close the loop',
            'body' => 'Retirement requests tie spending back to the original disbursement — with the difference and any refund due tracked alongside a full activity log of who approved what, and when.',
            'alt' => 'Retirement requests list showing amount expended and the difference against each advance',
        ],
    ],

    'bento' => [
        'heading' => 'See Flow Ledger at work.',
        'lede' => 'Five screens from a live workspace — the same views your finance team would open today.',
        'dashboard' => [
            'title' => 'Every branch, one screen',
            'text' => 'Cash balances, thresholds, and requests — the full picture the moment you sign in.',
        ],
        'workflow_stages' => [
            'title' => 'Chains you already run',
            'text' => 'Sequential and parallel approval stages, configured to match how your team signs off.',
        ],
        'payment_requests' => [
            'title' => 'Every request, tracked',
            'text' => 'Amounts, branches, and status for every payment and disbursement request.',
        ],
        'cashbook' => [
            'title' => 'Reconciled, branch by branch',
            'text' => "Cash counts against what's on hand, across every branch in one ledger.",
        ],
        'retirements' => [
            'title' => 'The loop, closed',
            'text' => 'Retirements tie spending back to the original disbursement, with the difference tracked automatically.',
        ],
    ],

    'spec' => [
        'heading' => 'Built for organizations, not just users.',
        'lede' => 'Every tenant is its own workspace, with its own people, permissions, and record of who did what.',
        'multi_tenant' => [
            'term' => 'Multi-tenant workspaces',
            'description' => 'Every organization gets an isolated workspace — its own branches, departments, roles, and requests.',
        ],
        'roles_permissions' => [
            'term' => 'Roles & permissions',
            'description' => 'Granular, per-tenant roles decide who can submit, approve, or administer — down to individual permissions.',
        ],
        'sso' => [
            'term' => 'Single sign-on',
            'description' => 'Sign in through your identity provider. Logout stays in sync across sessions via backchannel logout.',
        ],
        'activity_log' => [
            'term' => 'Activity log',
            'description' => 'Every request, approval, disbursement, and retirement is recorded and attributable.',
        ],
    ],

    'cta_strip' => [
        'heading' => 'Ready to route your first approval?',
        'lede' => 'Get started with your Flow Ledger workspace.',
        'get_started' => 'Get started',
    ],

    'footer' => [
        'tagline' => 'Approval workflows for finance teams',
    ],
];
