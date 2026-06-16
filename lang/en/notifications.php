<?php

declare(strict_types=1);

return [
    'greeting' => 'Hello :name,',

    'welcome' => [
        'subject' => 'Welcome to :app_name',
        'line_1' => 'An account has been created for you. You can use the credentials below to log in.',
        'password' => '**Temporary Password:** :password',
        'line_2' => 'For your security, you will be asked to change your password after your first login.',
        'action' => 'Log In',
    ],

    'low_cash_balance' => [
        'subject' => '⚠️ Low Cash Balance Alert: :branch',
        'greeting' => 'Hello,',
        'balance_fallen' => 'The cash balance for **:branch** has fallen below the configured threshold.',
        'current_balance' => '**Current Balance:** :amount',
        'threshold' => '**Threshold:** :amount',
        'take_action' => 'Please take necessary action to replenish the cash balance.',
        'automated' => 'This is an automated alert. Please do not reply to this email.',
    ],

    'request_approved' => [
        'subject' => 'Request #:id Fully Approved',
        'approved' => 'Your request has been **fully approved** and is now pending disbursement.',
        'amount' => '**Amount:** :amount',
        'finance' => 'Finance will process the disbursement shortly.',
        'action' => 'View Request',
    ],

    'request_disbursed' => [
        'subject' => 'Request #:id Has Been Disbursed',
        'disbursed' => 'Your approved request has been **disbursed**.',
        'amount' => '**Amount:** :amount',
        'method' => '**Method:** :method',
        'reference' => '**Reference:** :reference',
        'action' => 'View Request',
    ],

    'request_rejected' => [
        'subject' => 'Request #:id Rejected',
        'rejected' => 'Unfortunately, your request has been **rejected**.',
        'reason' => '**Reason:** :reason',
        'amount' => '**Amount:** :amount',
        'contact' => 'Please contact your approver if you have questions.',
        'action' => 'View Request',
    ],

    'request_sent_back' => [
        'subject' => 'Request #:id Sent Back for Revision',
        'sent_back' => 'Your request has been **sent back** for revision.',
        'feedback' => '**Feedback:** :feedback',
        'resubmit' => 'Please make the requested changes and resubmit your request.',
        'action' => 'View & Resubmit',
    ],

    'retirement_approved' => [
        'subject' => 'Retirement #:retirement_id Approved',
        'approved' => 'Your retirement for Advance #:pr_id has been **fully approved**.',
        'expended' => '**Amount Expended:** :amount',
        'settlement' => '**Settlement:** :type — :amount',
        'action' => 'View Retirement',
    ],

    'retirement_overdue' => [
        'submitter' => [
            'subject' => 'Action Required: Advance #:id is overdue for retirement',
            'line1' => 'Your advance disbursement **#:id** is overdue. You are required to submit a retirement (expense report) to account for the funds.',
            'amount' => '**Advance Amount:** :amount',
            'reminder' => 'Please submit your retirement with receipts and cost codes for all expenditures as soon as possible.',
            'action' => 'Submit Retirement',
        ],
        'approver' => [
            'subject' => 'Overdue Retirement: Advance #:id you approved has not been retired',
            'line1' => 'Advance **#:id** that you approved is overdue for retirement. The staff member has not yet submitted an expense report.',
            'amount' => '**Advance Amount:** :amount',
            'reminder' => 'This is an automated reminder.',
            'action' => 'View Advance',
        ],
        'default' => [
            'subject' => 'Overdue Retirement Alert: Advance #:id has not been retired',
            'line1' => 'Advance **#:id** is overdue for retirement. No expense report has been submitted for this disbursement.',
            'amount' => '**Advance Amount:** :amount',
            'reminder' => 'This is an automated reminder.',
            'action' => 'View Advance',
        ],
    ],

    'retirement_required' => [
        'subject' => 'Action Required: Retire Advance #:id',
        'line1' => 'You have received an advance disbursement. Please submit your **retirement (expense report)** to account for funds spent.',
        'amount' => '**Advance Amount:** :amount',
        'reminder' => 'Please submit your retirement as soon as possible with receipts and account codes for all expenditures.',
        'action' => 'Submit Retirement',
    ],

    'stage_ready' => [
        'subject' => 'Action Required: :stage — Request #:id',
        'waiting' => 'A request is waiting for your approval at the **:stage** stage.',
        'request' => '**Request:** #:id — :type',
        'amount' => '**Amount:** :amount',
        'login' => 'Please log in to approve, send back, or reject this request.',
        'action' => 'Review Request',
    ],
];
