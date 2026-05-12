<?php

declare(strict_types=1);

return [
    'title' => 'Levels Management',
    'subtitle' => 'Manage organisational levels and hierarchy',
    'add_new' => 'Add New Level',
    'all' => 'All Levels',
    'create_title' => 'Create Level',
    'create_subtitle' => 'Add a new level to your organisational hierarchy',
    'edit_title' => 'Edit Level',
    'edit_subtitle' => 'Update level details and hierarchy position',
    'back' => 'Back to Levels',
    'details_card' => 'Level Details',

    'fields' => [
        'name' => 'Level Name',
        'name_hint' => 'Enter a descriptive name for this level.',
        'position' => 'Position',
        'position_hint' => 'Hierarchy position (lower numbers = higher in hierarchy).',
    ],

    'buttons' => [
        'create' => 'Create Level',
        'update' => 'Update Level',
        'add' => 'Add Level',
        'delete' => 'Delete Level',
    ],

    'columns' => [
        'branches' => 'Branches',
    ],

    'empty' => [
        'heading' => 'No levels found',
        'subtext' => 'Get started by creating your first level',
    ],

    'confirm_delete' => 'Are you sure you want to delete this level? This action cannot be undone.',
    'confirm_delete_short' => 'Delete this level?',
];
