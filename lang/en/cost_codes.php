<?php

declare(strict_types=1);

return [
    'title' => 'Cost Codes',
    'subtitle' => 'Manage cost codes and their department assignments',
    'add_new' => 'Add New Cost Code',
    'all' => 'All Cost Codes',
    'create_title' => 'Create Cost Code',
    'create_subtitle' => 'Add a new cost code to your chart of accounts',
    'edit_title' => 'Edit Cost Code',
    'edit_subtitle' => 'Update cost code details',
    'back' => 'Back to Cost Codes',
    'details_card' => 'Cost Code Details',

    'fields' => [
        'code' => 'Code',
        'code_hint' => 'A unique identifier for this cost code.',
        'code_placeholder' => 'e.g. CC-1001',
        'name' => 'Name',
        'name_hint' => 'A descriptive name for this cost code.',
        'name_placeholder' => 'e.g. Office Supplies',
        'department' => 'Department',
        'select_department' => 'Select a department',
    ],

    'buttons' => [
        'create' => 'Create Cost Code',
        'update' => 'Update Cost Code',
        'add' => 'Add Cost Code',
        'delete' => 'Delete Cost Code',
    ],

    'empty' => [
        'heading' => 'No cost codes found',
        'subtext' => 'Get started by creating your first cost code',
    ],

    'confirm_delete' => 'Are you sure you want to delete this cost code? This action cannot be undone.',
];
