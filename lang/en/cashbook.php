<?php

declare(strict_types=1);

return [
    'title' => 'Cashbook',
    'subtitle' => 'Cash balances by branch',

    'index' => [
        'title' => 'Cashbook — :branch',
        'subtitle' => ':currency (:symbol) cash ledger',
        'add_receipt' => 'Add Receipt',
        'balance_card' => 'Current Balance',
        'entries_card' => 'Entries',
        'export' => 'Export CSV',
    ],

    'filter' => [
        'date_from' => 'From Date',
        'date_to' => 'To Date',
        'type' => 'Type',
        'description' => 'Description',
        'description_placeholder' => 'Search description or notes…',
        'amount_min' => 'Min Amount',
        'amount_max' => 'Max Amount',
    ],

    'branches' => [
        'title' => 'Cashbook',
        'subtitle' => 'Cash balances by branch',
        'card_heading' => 'Branches',
        'empty_heading' => 'No branches configured',
        'empty_subtext' => 'Create a branch to start tracking cash.',
        'view_cashbook' => 'View cashbook',
    ],

    'create' => [
        'title' => 'Add Receipt — :branch',
        'subtitle' => 'Record cash received from the bank or an external source',
        'back' => 'Back to Cashbook',
        'card' => 'Receipt Details',
        'save' => 'Save Receipt',
    ],

    'fields' => [
        'amount' => 'Amount (:symbol)',
        'date' => 'Date',
        'reference' => 'Reference Number',
        'reference_placeholder' => 'e.g. Bank transfer reference, cheque number',
        'notes' => 'Notes',
        'notes_placeholder' => 'Additional details about this receipt',
    ],

    'columns' => [
        'balance' => 'Balance',
    ],

    'entry_types' => [
        'debit' => 'Debit',
        'credit' => 'Credit',
    ],

    'empty' => [
        'heading' => 'No entries yet',
        'subtext' => 'Entries are created automatically when payments are disbursed or retirements are settled.',
        'add_manual' => 'Add Manual Receipt',
    ],

    'confirm_delete' => 'Delete this receipt?',
];
