<?php

declare(strict_types=1);

return [
    'title' => 'Currencies Management',
    'subtitle' => 'Manage currencies used across the system',
    'add_new' => 'Add New Currency',
    'all' => 'All Currencies',
    'create_title' => 'Create Currency',
    'create_subtitle' => 'Add a new currency to your system',
    'edit_title' => 'Edit Currency',
    'edit_subtitle' => 'Update currency information',
    'back' => 'Back to Currencies',
    'details_card' => 'Currency Details',

    'fields' => [
        'name' => 'Currency Name',
        'name_hint' => 'Enter the full name of the currency.',
        'name_placeholder' => 'e.g. Ghana Cedi, US Dollar',
        'code' => 'Currency Code',
        'code_hint' => 'Enter the ISO 4217 currency code (max 10 characters).',
        'code_placeholder' => 'e.g. GHS, USD',
        'symbol' => 'Currency Symbol',
        'symbol_hint' => 'Enter the currency symbol (max 10 characters).',
        'symbol_placeholder' => 'e.g. ₵, $',
    ],

    'buttons' => [
        'create' => 'Create Currency',
        'update' => 'Update Currency',
        'add' => 'Add Currency',
        'delete' => 'Delete Currency',
    ],

    'empty' => [
        'heading' => 'No currencies found',
        'subtext' => 'Get started by creating your first currency',
    ],

    'danger_zone' => 'Danger Zone',
    'delete_warning' => 'Permanently delete this currency. This action cannot be undone.',
    'confirm_delete' => 'Are you sure you want to delete this currency? This action cannot be undone.',
    'confirm_delete_short' => 'Are you sure you want to delete this currency?',
];
