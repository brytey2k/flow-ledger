<?php

declare(strict_types=1);

return [
    'title' => 'Users Management',
    'subtitle' => 'Manage users and their roles',
    'add_new' => 'Add New User',
    'all' => 'All Users',
    'create_title' => 'Create User',
    'create_subtitle' => 'Add a new user to your system',
    'edit_title' => 'Edit User',
    'edit_subtitle' => 'Update user information and roles',
    'back' => 'Back to Users',
    'details_card' => 'User Details',

    'fields' => [
        'first_name' => 'First Name',
        'last_name' => 'Last Name',
        'email' => 'Email',
        'password' => 'Password',
        'password_hint' => 'Leave blank to keep current password.',
        'confirm_password' => 'Confirm Password',
        'roles' => 'Roles',
        'no_roles' => 'No roles available.',
    ],

    'buttons' => [
        'create' => 'Create User',
        'update' => 'Update User',
        'add' => 'Add User',
        'delete' => 'Delete',
        'manage_perms' => 'Manage Permissions',
    ],

    'permissions' => [
        'title' => 'Manage User Permissions',
        'subtitle' => 'Assign direct permissions to :name',
        'back' => 'Back to Edit User',
        'card' => 'Direct Permissions',
        'description' => 'These are direct permissions assigned to this user, independent of their roles. The user will also have all permissions from their assigned roles.',
        'none' => 'No permissions available in the system.',
        'update' => 'Update Permissions',
    ],

    'columns' => [
        'roles' => 'Roles',
        'no_roles' => 'No roles',
    ],

    'empty' => [
        'heading' => 'No users found',
        'subtext' => 'Get started by creating your first user',
    ],

    'confirm_delete' => 'Are you sure you want to delete this user? This action cannot be undone.',
    'confirm_delete_short' => 'Are you sure you want to delete this user?',
];
