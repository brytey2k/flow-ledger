<?php

declare(strict_types=1);

return [
    'title' => 'Roles Management',
    'subtitle' => 'Manage roles and their permissions',
    'add_new' => 'Add New Role',
    'all' => 'All Roles',
    'create_title' => 'Create Role',
    'create_subtitle' => 'Add a new role to your system',
    'edit_title' => 'Edit Role',
    'edit_subtitle' => 'Update role information',
    'back' => 'Back to Roles',
    'details_card' => 'Role Details',

    'fields' => [
        'name' => 'Role Name',
        'name_hint' => 'Enter a descriptive name for this role.',
        'guard' => 'Guard Name',
        'guard_hint' => 'The authentication guard for this role (default: web).',
    ],

    'buttons' => [
        'create' => 'Create Role',
        'update' => 'Update Role',
        'add' => 'Add Role',
        'delete' => 'Delete',
        'manage_perms' => 'Manage Permissions',
    ],

    'permissions' => [
        'title' => 'Manage Role Permissions',
        'subtitle' => 'Assign permissions to :role role',
        'back' => 'Back to Edit Role',
        'card' => 'Role Permissions',
        'description' => 'Select the permissions that should be assigned to this role. All users with this role will inherit these permissions.',
        'select_all' => 'Select All Permissions',
        'none' => 'No permissions available in the system.',
        'update' => 'Update Permissions',
    ],

    'columns' => [
        'users' => 'Users',
        'permissions' => 'Permissions',
    ],

    'empty' => [
        'heading' => 'No roles found',
        'subtext' => 'Get started by creating your first role',
    ],

    'confirm_delete' => 'Are you sure you want to delete this role?',
];
