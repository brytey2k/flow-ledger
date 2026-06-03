<?php

declare(strict_types=1);

return [
    'title' => 'Retirement Requests',
    'subtitle' => 'Advance retirement and expense reconciliation',
    'all' => 'All Retirements',

    'create' => [
        'title' => 'Retire Request #:id',
        'subtitle' => 'Record actual expenditure for :name',
        'back' => 'Back to Request',
    ],

    'show' => [
        'title' => 'Retirement #:id',
        'for_advance' => 'For Advance',
        'back' => 'Back to Retirements',
        'summary_card' => 'Retirement Summary',
        'items_card' => 'Expenditure Items',
        'attachments_card' => 'Attachments',
        'timeline_card' => 'Timeline',
        'actions_card' => 'Actions',
        'approval_card' => 'Approval Progress',
        'sent_back_notice' => 'This retirement was sent back for review',
        'no_spend_notice' => 'This retirement was intentionally saved with no expenditure items.',
        'settlement_notes' => 'Settlement Notes',
        'no_attachments' => 'No attachments yet.',
        'confirm_delete_attachment' => 'Delete this attachment?',
    ],

    'fields' => [
        'advance_summary' => 'Advance Summary',
        'advance_amount' => 'Advance Amount',
        'expenditure_items' => 'Expenditure Items',
        'did_not_spend_money' => 'I did not spend any of this advance',
        'no_spend_warning' => 'Check this only if nothing was spent. Saving with no expenditure items will mark the retirement as intentionally empty and the full advance will still need to be accounted for.',
        'no_spend_note' => 'Expenditure items are disabled while this is checked.',
        'description' => 'Description',
        'what_purchased' => 'What was purchased',
        'cost_code' => 'Cost Code',
        'receipt_no' => 'Receipt No.',
        'notes' => 'Notes',
        'notes_placeholder' => 'Optional notes…',
        'summary' => 'Summary',
        'total_expended' => 'Total Expended',
        'difference' => 'Difference',
    ],

    'difference' => [
        'nil' => 'No difference — fully retired.',
        'pay_to_staff' => 'Staff spent more — company owes the difference.',
        'refund' => 'Staff spent less — refund required.',
    ],

    'status' => [
        'pay_to_staff' => 'Pay to Staff',
        'refund_company' => 'Refund to Company',
        'nil' => 'No Difference',
        'fully_approved' => 'Fully approved',
        'settled' => 'Settled',
        'cancelled' => 'Cancelled',
    ],

    'buttons' => [
        'save_draft' => 'Save as Draft',
        'save_changes' => 'Save Changes',
        'submit' => 'Submit for Approval',
        'resubmit' => 'Resubmit for Approval',
        'edit_request' => 'Edit Retirement',
        'settle' => 'Mark as Settled',
        'upload' => 'Upload',
    ],

    'edit' => [
        'title' => 'Edit Retirement #:id',
        'subtitle' => 'Update your retirement before resubmitting for approval',
        'back' => 'Back to Retirement',
    ],

    'timeline' => [
        'created_draft' => 'Created as draft',
        'submitted' => 'Submitted for approval',
        'fully_approved' => 'Fully approved',
        'cancelled' => 'Cancelled',
        'settled' => 'Difference settled',
        'resubmitted' => 'Resubmitted for approval',
        'updated' => 'Retirement updated',
        'stage_approved' => 'Stage approved',
        'stage_rejected' => 'Stage rejected',
        'sent_back' => 'Sent back for revision',
        'no_activity' => 'No activity yet.',
    ],

    'columns' => [
        'advance' => 'Advance',
        'amount_expended' => 'Amount Expended',
        'difference' => 'Difference',
    ],

    'empty' => [
        'heading' => 'No retirements yet',
        'subtext' => 'Retirements are created from disbursed advance requests.',
    ],
];
