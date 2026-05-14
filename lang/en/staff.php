<?php

declare(strict_types=1);

return [
    'title' => 'Staff',
    'subtitle' => 'Manage staff members in your organisation',
    'add_new' => 'Add New Staff',
    'all' => 'All Staff',
    'create_title' => 'Add Staff Member',
    'create_subtitle' => 'Add a new staff member to your organisation',
    'edit_title' => 'Edit Staff Member',
    'edit_subtitle' => 'Update staff member details',
    'back' => 'Back to Staff',
    'details_card' => 'Staff Details',

    'fields' => [
        'first_name' => 'First Name',
        'last_name' => 'Last Name',
        'email' => 'Email',
        'phone' => 'Phone',
        'department' => 'Department',
        'select_department' => 'Select a department',
        'position' => 'Position',
        'select_position' => 'Select a position',
        'branch' => 'Branch',
        'select_branch' => 'Select a branch',
        'user_account' => 'User Account',
        'no_linked_account' => 'No linked account',
        'create_user_toggle' => 'Grant login access',
        'create_user_hint' => 'Create a login account so this staff member can access the system.',
        'user_email' => 'Login Email',
        'user_password' => 'Password',
        'user_password_confirmation' => 'Confirm Password',
        'user_roles' => 'Roles',
        'select_roles' => 'Select roles (optional)',
        'login_access_card' => 'Login Account',
        'linked_account_readonly' => 'This staff member is linked to a user account. To change it, manage users directly.',
        'assign_login_toggle' => 'Assign login access',
        'assign_login_hint' => 'Link this staff member to a user account so they can log in.',
        'user_action_create' => 'Create new account',
        'user_action_link' => 'Link existing account',
        'link_user' => 'User Account',
        'select_user' => 'Select a user',
    ],

    'buttons' => [
        'create' => 'Add Staff Member',
        'update' => 'Update Staff Member',
        'add' => 'Add Staff',
        'delete' => 'Delete Staff Member',
    ],

    'empty' => [
        'heading' => 'No staff found',
        'subtext' => 'Get started by adding your first staff member',
    ],

    'confirm_delete' => 'Are you sure you want to delete this staff member? This action cannot be undone.',
];
