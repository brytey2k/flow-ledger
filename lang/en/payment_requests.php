<?php

declare(strict_types=1);

return [
    'title' => 'Payment Requests',
    'subtitle' => 'Manage advance and expense reimbursement requests',
    'add_new' => 'New Request',
    'all' => 'All Requests',

    'create_title' => 'New Payment Request',
    'create_subtitle' => 'Submit an advance or expense reimbursement request',
    'edit_title' => 'Edit Request #:id',
    'edit_subtitle' => 'Update your request before resubmitting for approval',
    'back' => 'Back to Requests',
    'details_card' => 'Request Details',

    'fields' => [
        'type' => 'Request Type',
        'select_type' => 'Select type…',
        'type_advance' => 'Advance — request funds before spending',
        'type_expense' => 'Expense — reimburse money already spent',
        'submitting_as' => 'Submitting As',
        'currency' => 'Currency',
        'select_currency' => 'Select currency…',
        'notes' => 'Notes',
        'line_items' => 'Line Items',
        'line_items_hint' => 'Add each item to be funded or reimbursed',
        'description' => 'Description',
        'account_code' => 'Account Code',
        'select' => 'Select…',
        'receipt_number' => 'Receipt Number',
    ],

    'buttons' => [
        'save_draft' => 'Save as Draft',
        'save_changes' => 'Save Changes',
        'submit' => 'Submit for Approval',
        'resubmit' => 'Resubmit for Approval',
        'edit_request' => 'Edit Request',
        'delete_draft' => 'Delete Draft',
        'disburse' => 'Mark as Disbursed',
        'retire' => 'Retire this Advance',
        'view_retirement' => 'View Retirement',
        'review_and_approve' => 'Review & Approve',
    ],

    'show' => [
        'title' => 'Request #:id',
        'back' => 'Back to Requests',
        'request_details' => 'Request Details',
        'staff_member' => 'Staff Member',
        'currency' => 'Currency',
        'total_amount' => 'Total Amount',
        'submitted' => 'Submitted',
        'approved' => 'Approved',
        'notes' => 'Notes',
        'line_items' => 'Line Items',
        'account_code' => 'Account Code',
        'receipt' => 'Receipt',
        'timeline' => 'Timeline',
        'actions' => 'Actions',
        'approval_progress' => 'Approval Progress',
        'payment_method' => 'Payment Method',
        'transaction_ref' => 'Transaction ref / cheque no.',
        'disbursed_on' => 'Disbursed on',
        'method_label' => 'Method:',
        'ref_label' => 'Ref:',
        'sent_back_notice' => 'This request was sent back for review',
    ],

    'status' => [
        'awaiting_approval' => 'Awaiting approval',
        'approved_awaiting' => 'Fully approved — awaiting disbursement',
        'cancelled' => 'Cancelled',
    ],

    'timeline' => [
        'created_draft' => 'Created as draft',
        'submitted' => 'Submitted for approval',
        'fully_approved' => 'Fully approved',
        'cancelled' => 'Cancelled',
        'resubmitted' => 'Resubmitted for approval',
        'updated' => 'Request updated',
        'stage_approved' => 'Stage approved',
        'stage_rejected' => 'Stage rejected',
        'sent_back' => 'Sent back for revision',
        'disbursed' => 'Disbursed',
        'no_activity' => 'No activity yet.',
    ],

    'confirm_delete_draft' => 'Delete this draft? This cannot be undone.',
    'confirm_disburse' => 'Mark this request as disbursed?',

    'empty' => [
        'heading' => 'No requests yet',
        'subtext' => 'Create a new advance or expense request to get started',
    ],

    'types' => [
        'advance' => 'Advance',
        'expense' => 'Expense',
    ],
];
