<?php

declare(strict_types=1);

return [
    'attachments' => [
        'uploaded' => 'Attachment uploaded.',
        'deleted' => 'Attachment deleted.',
    ],
    'cashbook' => [
        'receipt_recorded' => 'Receipt recorded successfully.',
        'receipt_deleted' => 'Receipt deleted successfully.',
        'auto_entry_delete_forbidden' => 'Auto-generated entries cannot be deleted.',
    ],
    'comments' => [
        'added' => 'Comment added.',
        'deleted' => 'Comment deleted.',
    ],
    'currencies' => [
        'created' => 'Currency created successfully.',
        'updated' => 'Currency updated successfully.',
        'deleted' => 'Currency deleted successfully.',
    ],
    'levels' => [
        'created' => 'Level created successfully.',
        'updated' => 'Level updated successfully.',
        'deleted' => 'Level deleted.',
        'delete_blocked_branches' => 'Cannot delete a level that has branches assigned to it.',
    ],
    'positions' => [
        'created' => 'Position created successfully.',
        'updated' => 'Position updated successfully.',
        'deleted' => 'Position deleted.',
        'delete_blocked_staff' => 'Cannot delete a position that has staff assigned to it.',
    ],
    'departments' => [
        'created' => 'Department created successfully.',
        'updated' => 'Department updated successfully.',
        'deleted' => 'Department deleted.',
    ],
    'account_codes' => [
        'created' => 'Account code created successfully.',
        'updated' => 'Account code updated successfully.',
        'deleted' => 'Account code deleted.',
    ],
    'branches' => [
        'created' => 'Branch created successfully.',
        'updated' => 'Branch updated successfully.',
        'deleted' => 'Branch deleted.',
        'delete_blocked_staff' => 'Cannot delete a branch that has staff assigned to it.',
        'delete_blocked_children' => 'Cannot delete a branch that has child branches.',
    ],
    'staff' => [
        'created' => 'Staff member created successfully.',
        'updated' => 'Staff member updated successfully.',
        'deleted' => 'Staff member deleted.',
    ],
    'users' => [
        'created' => 'User created successfully.',
        'updated' => 'User updated successfully.',
        'deleted' => 'User deleted successfully.',
        'permissions_updated' => 'User permissions updated successfully.',
    ],
    'roles' => [
        'created' => 'Role created successfully.',
        'updated' => 'Role updated successfully.',
        'deleted' => 'Role deleted successfully.',
        'permissions_updated' => 'Role permissions updated successfully.',
        'delete_blocked_users' => 'Cannot delete role that has associated users.',
    ],
    'workflows' => [
        'template_created' => 'Workflow template created. Now add stages.',
        'template_updated' => 'Workflow template updated.',
        'template_deleted' => 'Workflow template deleted.',
        'stage_added' => 'Stage added.',
        'stage_updated' => 'Stage updated.',
        'stage_deleted' => 'Stage deleted.',
        'parallel_group_created' => 'Parallel group created.',
        'parallel_group_deleted' => 'Parallel group deleted.',
    ],
    'requests' => [
        'missing_staff_profile' => 'Your account is not linked to a staff profile with a branch. Please contact an administrator.',
        'draft_saved' => 'Request saved as draft.',
        'draft_delete_only' => 'Only draft requests can be deleted.',
        'deleted' => 'Request deleted.',
        'submitted' => 'Request submitted for approval.',
        'resubmitted' => 'Request resubmitted for approval.',
        'submit_only_draft' => 'Only draft requests can be submitted.',
        'resubmit_only_sent_back' => 'Only sent-back requests can be resubmitted.',
        'missing_workflow_template' => 'No workflow template configured for this request type. Please ask an administrator to set one up.',
        'disburse_only_approved' => 'Only approved requests can be disbursed.',
        'disbursed' => 'Request marked as disbursed.',
    ],
    'retirements' => [
        'draft_saved' => 'Retirement saved as draft.',
        'submitted' => 'Retirement submitted for approval.',
        'resubmitted' => 'Retirement resubmitted for approval.',
        'submit_only_draft' => 'Only draft retirements can be submitted.',
        'resubmit_only_sent_back' => 'Only sent-back retirements can be resubmitted.',
        'settled' => 'Retirement marked as settled.',
        'settle_only_approved' => 'Only approved retirements can be settled.',
    ],
    'approvals' => [
        'action_recorded' => 'Action recorded successfully.',
    ],
    'tenants' => [
        'created' => 'Tenant created successfully.',
        'suspended' => 'Tenant suspended.',
        'reactivated' => 'Tenant reactivated.',
        'reset_failed' => 'Failed to reset tenant.',
        'reset_confirm_mismatch' => 'Tenant reset canceled: confirmation name does not match.',
        'reset_success' => 'Tenant reset successfully.',
        'delete_failed' => 'Failed to delete tenant.',
        'delete_confirm_mismatch' => 'Tenant deletion canceled: confirmation name does not match.',
        'deleted' => 'Tenant deleted successfully.',
    ],
    'feature_flags' => [
        'updated' => 'Feature flags updated.',
        'bulk_updated' => 'Bulk feature flag update applied.',
    ],
];
