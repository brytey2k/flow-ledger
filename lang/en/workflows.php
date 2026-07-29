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
    'identity_locked_hint' => 'Type and branch scope are permanent once a template is created and cannot be changed.',

    'fields' => [
        'name' => 'Template Name',
        'type' => 'Type',
        'select_type' => 'Select type',
        'type_advance' => 'Advance',
        'type_expense' => 'Expense',
        'type_retirement' => 'Retirement',
        'type_hint' => 'Advance = payment advance requests • Expense = out-of-pocket reimbursements • Retirement = retiring an advance',
        'branch' => 'Branch',
        'branch_master' => 'Master (all branches)',
        'branch_hint' => 'Leave blank to use this as the default workflow for all branches.',
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
        'branch' => 'Branch',
        'master' => 'Master',
    ],

    'show' => [
        'version_history' => 'Version History',
        'version_badge' => 'Version :number',
        'superseded_badge' => 'Superseded',
        'draft_badge' => 'Draft',
        'draft_banner' => 'You\'re editing a draft. Changes here won\'t affect live payment requests until you publish.',
        'publish' => 'Publish',
        'discard_draft' => 'Discard Draft',
        'confirm_publish' => 'Publish this draft? It will become the active version for new payment requests.',
        'confirm_discard' => 'Discard this draft? All changes made here will be permanently lost.',
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
            'approver_scope' => 'Approver Scope',
        ],
        'column_tips' => [
            'order' => 'Stages run lowest number first. Stages that share the same number run at the same time. Example: stage 1 (Finance Review) finishes before stage 2 (CFO Approval) starts.',
            'stage_name' => 'A label for this approval step, shown to approvers in their inbox and on the request timeline. Example: "Department Head Approval".',
            'roles' => 'Any user holding one of the listed roles can approve or reject at this stage. Example: assigning "Finance Manager" lets every Finance Manager act on it.',
            'parallel_group' => 'Groups stages that run simultaneously instead of one after another. ALL requires every stage in the group to approve before moving on; ANY advances the request as soon as one stage approves. Example: a "Finance & HR" group set to ALL needs sign-off from both before the next stage begins.',
            'skip_below' => 'This stage is skipped automatically when the request total is below this amount. Example: a value of 500 means requests under 500 bypass the stage entirely.',
            'approver_scope' => 'Further restricts who can approve, beyond having the required role. Example: Branch scope means only approvers based in the same branch as the requester can act on this stage.',
        ],
        'sequential' => 'Sequential',
        'and_label' => 'AND',
        'or_label' => 'OR',
        'scope_branch' => 'Branch',
        'scope_department' => 'Department',
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
            'scope_to_department' => 'Restrict approvers to submitter\'s department',
            'scope_to_branch' => 'Restrict approvers to request\'s branch',
        ],
        'buttons' => [
            'add' => 'Add Stage',
            'save' => 'Save Changes',
            'delete' => 'Delete Stage',
        ],
        'confirm_delete' => 'Delete this stage?',
        'sync_warning' => [
            'heading' => 'This will reorder other stages too',
            'body' => 'To keep this parallel group running together, {names} will also be updated to display order {order}.',
            'proceed' => 'Yes, update and save',
            'cancel' => 'Cancel',
        ],
    ],

    'versions' => [
        'title' => 'Version History',
        'subtitle' => 'Past and current versions of this workflow template',
        'back' => 'Back to Template',
        'columns' => [
            'version' => 'Version',
            'created_at' => 'Created',
            'status' => 'Status',
            'total_requests' => 'Total Requests',
            'active_requests' => 'Active Requests',
        ],
        'current' => 'Current',
        'superseded' => 'Superseded',
        'draft' => 'Draft',
    ],
];
