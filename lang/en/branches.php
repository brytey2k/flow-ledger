<?php

declare(strict_types=1);

return [
    'title' => 'Branches Management',
    'subtitle' => 'Manage organisational branches and hierarchy',
    'add_new' => 'Add New Branch',
    'all' => 'All Branches',
    'create_title' => 'Create Branch',
    'create_subtitle' => 'Add a new branch to your organisational structure',
    'edit_title' => 'Edit Branch',
    'edit_subtitle' => 'Update branch details and hierarchy',
    'back' => 'Back to Branches',
    'details_card' => 'Branch Details',

    'fields' => [
        'name' => 'Branch Name',
        'name_hint' => 'Enter a descriptive name for this branch.',
        'name_placeholder' => 'e.g. Accra Regional Office',
        'code' => 'Branch Code',
        'code_hint' => 'Optional unique identifier for this branch.',
        'code_placeholder' => 'e.g. ACC-REG',
        'position' => 'Position',
        'position_hint' => 'Display order within the same level.',
        'level' => 'Level',
        'level_hint' => 'Organisational level for this branch.',
        'select_level' => 'Select a level…',
        'currency' => 'Reporting Currency',
        'currency_hint' => 'Currency used for reporting in this branch.',
        'select_currency' => 'Select a currency…',
        'parent' => 'Parent Branch',
        'parent_hint' => 'Leave empty for root branch, or select a parent.',
        'none_root' => 'None (Root Branch)',
    ],

    'buttons' => [
        'create' => 'Create Branch',
        'update' => 'Update Branch',
        'add' => 'Add Branch',
        'delete' => 'Delete Branch',
    ],

    'empty' => [
        'heading' => 'No branches found',
        'subtext' => 'Get started by creating your first branch',
    ],

    'descendants_warning' => 'Branch Has Descendants',
    'descendants_message' => 'This branch has :count :child. Changing the parent may affect the hierarchy structure.',
    'confirm_delete' => 'Are you sure you want to delete this branch? This action cannot be undone.',
    'confirm_delete_short' => 'Delete this branch?',

    'columns' => [
        'branch_name' => 'Branch Name',
    ],
];
