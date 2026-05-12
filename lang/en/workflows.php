<?php

declare(strict_types=1);

return [
    'title' => 'Workflow Templates',
    'subtitle' => 'Configure approval workflows for requests',
    'add_new' => 'New Template',
    'all' => 'All Templates',

    'create_title' => 'New Workflow Template',
    'create_subtitle' => 'Define a reusable approval workflow',
    'edit_title' => 'Edit Template',
    'back' => 'Back',
    'details_card' => 'Template Details',

    'fields' => [
        'name' => 'Template Name',
        'type' => 'Type',
        'select_type' => 'Select type',
        'type_advance' => 'Advance',
        'type_expense' => 'Expense',
        'type_retirement' => 'Retirement',
        'type_hint' => 'Advance = payment advance requests • Expense = out-of-pocket reimbursements • Retirement = retiring an advance',
    ],

    'buttons' => [
        'create' => 'Create Template',
        'update' => 'Save Changes',
        'add' => 'New Template',
        'delete' => 'Delete Template',
        'configure' => 'Configure',
    ],

    'columns' => [
        'stages' => 'Stages',
    ],

    'show' => [
        'parallel_groups' => 'Parallel Groups',
        'parallel_groups_hint' => 'Assign stages to a group to run them simultaneously',
        'add_group' => 'Add Group',
        'delete_group' => 'Delete this group?',
        'approval_stages' => 'Approval Stages',
        'stages_hint' => 'Stages run in order (lower number first). Same order = parallel.',
        'no_stages' => 'No stages yet. Add the first approval stage below.',
        'add_stage' => 'Add Stage',
        'columns' => [
            'order' => 'Order',
            'stage_name' => 'Stage Name',
            'roles' => 'Roles',
            'parallel_group' => 'Parallel Group',
            'skip_below' => 'Skip Below',
        ],
        'sequential' => 'Sequential',
        'and_label' => 'AND',
        'or_label' => 'OR',
    ],

    'empty' => [
        'heading' => 'No templates yet',
        'subtext' => 'Create your first workflow template to enable approvals',
    ],

    'confirm_delete' => 'Delete this template? All stages will be removed.',

    'stages' => [
        'add_title' => 'Add Stage',
        'edit_title' => 'Edit Stage',
        'back' => 'Back',
        'details_card' => 'Stage Details',
        'fields' => [
            'name' => 'Stage Name',
            'display_order' => 'Display Order',
            'order_hint' => 'Lower numbers run first. Same number = parallel.',
            'skip_below' => 'Skip Below Amount',
            'skip_below_hint' => 'Auto-skip this stage when request total is below this amount. Leave empty to never skip.',
            'skip_below_hint_short' => 'Leave empty to never skip',
            'parallel_group' => 'Parallel Group',
            'none_sequential' => 'None (sequential)',
            'all_must_approve' => 'ALL must approve',
            'any_approves' => 'ANY one approves',
            'roles_label' => 'Roles that can approve this stage',
            'roles_hint' => 'Any user with one of these roles will see this stage in their approvals inbox.',
        ],
        'buttons' => [
            'add' => 'Add Stage',
            'save' => 'Save Changes',
            'delete' => 'Delete Stage',
        ],
        'confirm_delete' => 'Delete this stage?',
    ],
];
