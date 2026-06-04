<?php

declare(strict_types=1);

return [
    'title' => 'Departments Management',
    'subtitle' => 'Manage organisational departments',
    'add_new' => 'Add New Department',
    'import' => 'Import Departments',
    'all' => 'All Departments',
    'create_title' => 'Create Department',
    'create_subtitle' => 'Add a new department to your organisation',
    'import_title' => 'Import Departments',
    'import_subtitle' => 'Upload a CSV file or download the sample template to bulk create departments.',
    'edit_title' => 'Edit Department',
    'edit_subtitle' => 'Update department details',
    'back' => 'Back to Departments',
    'details_card' => 'Department Details',
    'import_card' => 'Import File',
    'sample_card' => 'CSV Template',

    'fields' => [
        'name' => 'Department Name',
        'name_hint' => 'Enter a descriptive name for this department.',
        'file' => 'CSV File',
        'file_hint' => 'Upload a CSV with a single name column.',
    ],

    'buttons' => [
        'create' => 'Create Department',
        'update' => 'Update Department',
        'add' => 'Add Department',
        'import' => 'Import Departments',
        'download_sample' => 'Download Sample CSV',
        'delete' => 'Delete Department',
    ],

    'empty' => [
        'heading' => 'No departments found',
        'subtext' => 'Get started by creating your first department',
    ],

    'confirm_delete' => 'Are you sure you want to delete this department? This action cannot be undone.',
    'import_notes' => 'The CSV should contain a single header named name. Example rows are included in the sample file.',

    'import_errors' => [
        'unreadable' => 'The uploaded CSV could not be read.',
        'empty' => 'The CSV file is empty.',
        'no_rows' => 'The CSV must include at least one department row.',
        'invalid_headers' => 'The CSV must contain a single header named name.',
        'name_required' => 'Row :row: the department name is required.',
        'name_too_long' => 'Row :row: ":name" exceeds the 100 character limit.',
        'duplicate_in_file' => 'Row :row: ":name" appears more than once in the CSV.',
        'duplicate_existing' => 'Row :row: ":name" already exists.',
    ],
];
