<?php

declare(strict_types=1);

return [
    'title' => 'Account Codes',
    'subtitle' => 'Manage account codes and their department assignments',
    'add_new' => 'Add New Account Code',
    'all' => 'All Account Codes',
    'create_title' => 'Create Account Code',
    'create_subtitle' => 'Add a new account code to your chart of accounts',
    'edit_title' => 'Edit Account Code',
    'edit_subtitle' => 'Update account code details',
    'back' => 'Back to Account Codes',
    'details_card' => 'Account Code Details',

    'fields' => [
        'code' => 'Code',
        'code_hint' => 'A unique identifier for this account code.',
        'code_placeholder' => 'e.g. ACC-1001',
        'name' => 'Name',
        'name_hint' => 'A descriptive name for this account code.',
        'name_placeholder' => 'e.g. Office Supplies',
        'department' => 'Department',
        'select_department' => 'Select a department',
    ],

    'buttons' => [
        'create' => 'Create Account Code',
        'update' => 'Update Account Code',
        'add' => 'Add Account Code',
        'delete' => 'Delete Account Code',
    ],

    'empty' => [
        'heading' => 'No account codes found',
        'subtext' => 'Get started by creating your first account code',
    ],

    'confirm_delete' => 'Are you sure you want to delete this account code? This action cannot be undone.',
];
