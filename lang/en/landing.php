<?php

declare(strict_types=1);

return [
    'meta' => [
        'title_suffix' => 'Staff advances and expenses, approved and accounted for',
        'description' => 'Flow Ledger takes staff advances and expense claims from request to approval, payout and retirement, with a record of every signature, branch cashbooks and reports for finance and audit.',
    ],

    'nav' => [
        'skip' => 'Skip to content',
        'footer' => 'Footer',
        'videos' => 'Videos',
        'primary' => 'Primary',
        'how_it_works' => 'How it works',
        'security' => 'Controls',
        'film' => 'Video',
        'user_guide' => 'User guide',
        'book_demo' => 'Book a demo',
        'toggle_menu' => 'Menu',
        'language' => 'Language',
        'dark_mode' => 'Dark mode',
        'home' => 'Flow Ledger home',
    ],

    'hero' => [
        'headline' => 'Every cedi advanced, accounted for.',
        'lede' => 'Flow Ledger takes staff advances and expense claims from request to approval, payout and retirement. Every signature is on record, every advance is followed up until it is retired, and finance can see the cash at every branch.',
        'book_demo' => 'Book a demo',
        'see_how_it_works' => 'See how it works',
        'watch_film' => 'Watch the video',
        'call_prompt' => 'Prefer to talk? Call or WhatsApp',
        'shot_alt' => 'The Flow Ledger dashboard: pending approvals, pending payouts, money disbursed in the last 30 days, overdue advances and a branch below its cash threshold',
        'shot_caption' => 'The dashboard of a demonstration organisation.',
    ],

    'problem' => [
        'heading' => 'Where paper approvals lose money.',
        'lede' => 'Most finance teams know these problems well. Each one has a direct answer in Flow Ledger.',
        'without' => 'Today',
        'with' => 'With Flow Ledger',
        'rows' => [
            'signatures' => [
                'problem' => 'A request waits days for the next signature, because someone is always travelling.',
                'answer' => 'Requests move through your approval stages online. Each approver has an inbox of what is waiting for them, and nothing is lost in a folder.',
            ],
            'unretired' => [
                'problem' => 'Advances are paid out and never retired.',
                'answer' => 'Every disbursed advance waits for a retirement. After your grace period, reminders go to the staff member, the approvers or the roles you choose, until it is settled.',
            ],
            'cash' => [
                'problem' => 'The cash on hand does not match the book, and nobody can explain why.',
                'answer' => 'Each branch keeps a cashbook that payouts and settlements post to automatically. Cash counts show any surplus or deficit against it.',
            ],
            'trail' => [
                'problem' => 'The auditor asks who approved a payment, and nobody can say.',
                'answer' => 'Every request carries its own timeline of who did what, and when. The activity log cannot be edited or deleted, and it is kept for seven years.',
            ],
        ],
    ],

    'film' => [
        'seconds' => ':count second|:count seconds',
        'heading' => 'Flow Ledger in :length.',
        'lede' => 'A short video of the whole cycle: a payment request, its approvals, the payout, the retirement and the reports.',
        'walkthrough' => 'Watch the full walkthrough',
        'more_videos' => 'More videos',
        'user_guide' => 'Read the user guide',
    ],

    'steps' => [
        'heading' => 'From request to retirement, in one place.',
        'lede' => 'The five steps your finance team already follows, with the system keeping the record. The screens below are from a demonstration organisation.',
        'request' => [
            'title' => 'Staff request an advance or claim an expense',
            'body' => 'Each line item carries a cost code, receipts are attached, and the request belongs to the staff member\'s branch and department from the start.',
            'points' => [
                'An advance before spending, or an expense to reimburse',
                'Receipts required before submission, if you turn that on',
                'Save it as a draft, and submit it when it is ready',
            ],
            'alt' => 'A draft advance request with its request type, currency, notes and line items',
        ],
        'approve' => [
            'title' => 'The right people approve, in the right order',
            'body' => 'You decide the stages: head of department, finance officer, finance director, or whoever signs off in your organisation. Stages can run in parallel, and small amounts can skip the stages you choose.',
            'points' => [
                'Approve, send back for correction, or reject, with a comment',
                'A request sent back resumes at the stage that returned it',
                'Nobody can approve their own request',
            ],
            'alt' => 'An approver reviewing a request, with the Approve, Send Back and Reject buttons beside it',
        ],
        'disburse' => [
            'title' => 'Finance pays out from the branch cashbook',
            'body' => 'Approved requests wait in one queue, beside the cash each branch has available. Paying out records the payment in that branch\'s cashbook.',
            'points' => [
                'Whoever requested or approved a request cannot pay it out',
                'Each payout is checked against the branch cashbook balance',
                'Record a transaction reference or cheque number',
            ],
            'alt' => 'The disbursements queue showing the cash available for release at each branch',
        ],
        'retire' => [
            'title' => 'Staff account for every advance',
            'body' => 'Staff list what they actually spent, with receipts. Flow Ledger works out the difference: a refund due to the organisation, or a balance owed to the staff member.',
            'points' => [
                'Retirements go through their own approval workflow',
                'Settling posts the difference to the cashbook',
                'Reminders continue until the advance is retired',
            ],
            'alt' => 'A retirement listing what was spent against an advance, with the difference to refund',
        ],
        'report' => [
            'title' => 'See where the money is, and where it is stuck',
            'body' => 'Reports for finance, management and audit, filtered by date and branch, and exported to CSV or PDF.',
            'points' => [
                'Outstanding advances, by how long they have been out',
                'Cash position, disbursements and spending by branch',
                'Approval turnaround, send-back rate and workflow SLA',
            ],
            'alt' => 'The Outstanding Advances report, with advances grouped by how many days they have been outstanding',
        ],
    ],

    'controls' => [
        'heading' => 'Answers for the people who sign off.',
        'lede' => 'What finance, internal audit and IT ask before they adopt a system that handles money.',
        'shot_alt' => 'A request timeline listing each step: created, submitted, and each stage approved, with who did it and when',
        'shot_caption' => 'Every request keeps its own timeline: who did what, and when.',
        'items' => [
            'segregation' => [
                'question' => 'Can one person request, approve and pay out the same advance?',
                'answer' => 'No. A requester cannot approve their own request, a person can act at only one stage of a workflow, and nobody who requested or approved a request can pay it out. If no independent approver is available, the stage waits instead of being skipped.',
            ],
            'audit' => [
                'question' => 'Can anyone change the record afterwards?',
                'answer' => 'Requests, approvals, payouts, cashbook entries, cash counts and changes to users, roles and settings are all recorded in the activity log. Its entries cannot be edited or deleted in Flow Ledger, and they are kept for seven years. Cashbook entries made by payouts and settlements cannot be deleted either.',
            ],
            'branches' => [
                'question' => 'We have many branches. Who sees what?',
                'answer' => 'Branches form a tree, from head office down. By default people see only their own branch\'s requests, cashbooks and reports; a separate permission extends that to a branch and every branch beneath it. Approval stages can be limited to the request\'s branch or the requester\'s department.',
            ],
            'roles' => [
                'question' => 'Who decides what each person can do?',
                'answer' => 'Your administrators. Every action is its own permission and roles bundle them, so you can, for example, create an approver role that only reviews requests. Nobody can grant a permission they do not hold themselves.',
            ],
            'sso' => [
                'question' => 'Can staff sign in with their work accounts?',
                'answer' => 'Yes. Flow Ledger supports single sign-on through an identity provider. An account is created the first time someone signs in, and it can be linked to their staff record automatically. Signing out stays in step across sessions.',
            ],
            'isolation' => [
                'question' => 'Is our data kept apart from other organisations?',
                'answer' => 'Yes. Each organisation has its own database and its own file storage.',
            ],
            'export' => [
                'question' => 'Can we take our data out?',
                'answer' => 'Every report exports to CSV and PDF, and each branch cashbook exports to CSV. Going the other way, staff, departments and positions can be imported from spreadsheets when you start.',
            ],
            'currency' => [
                'question' => 'Does it work in cedis, and in other currencies?',
                'answer' => 'Yes. Each request is in the currency you choose, each branch keeps its cashbook in its reporting currency, and cash counts use the notes and coins you set up for that currency.',
            ],
        ],
    ],

    'contact' => [
        'heading' => 'See Flow Ledger with your own approval chain.',
        'lede' => 'We set up each organisation with its team, so self-service sign-up is paused. Book a demonstration and we will walk your finance team through Flow Ledger, then set it up with you.',
        'call' => 'Call :phone',
        'whatsapp' => 'WhatsApp us',
        'whatsapp_message' => 'Hello, I would like a demonstration of Flow Ledger.',
        'next_heading' => 'What happens next',
        'next' => [
            'demo' => 'A demonstration for your finance team, built around how your organisation approves payments today.',
            'workspace' => 'We create your organisation\'s workspace, with a Head Office branch and an administrator account.',
            'setup' => 'You add your branches, roles and approval workflows, and import your staff from a spreadsheet.',
        ],
    ],

    'footer' => [
        'statement' => 'Staff advances, expenses and retirements for organisations where more than one person signs off.',
        'copyright' => '© :year Flow Ledger',
    ],
];
