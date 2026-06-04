<?php

declare(strict_types=1);

return [
    'title' => 'Positions',
    'subtitle' => 'Manage staff positions in your organisation',
    'add_new' => 'Add New Position',
    'import' => 'Import Positions',
    'all' => 'All Positions',
    'create_title' => 'Create Position',
    'create_subtitle' => 'Add a new position to your organisation',
    'import_title' => 'Import Positions',
    'import_subtitle' => 'Upload a CSV file or download the sample template to bulk create positions.',
    'edit_title' => 'Edit Position',
    'edit_subtitle' => 'Update position details',
    'back' => 'Back to Positions',
    'details_card' => 'Position Details',
    'import_card' => 'Import File',
    'sample_card' => 'CSV Template',

    'fields' => [
        'name' => 'Position Name',
        'name_hint' => 'Enter a descriptive name for this position.',
        'file' => 'CSV File',
        'file_hint' => 'Upload a CSV with a single name column.',
    ],

    'buttons' => [
        'create' => 'Create Position',
        'update' => 'Update Position',
        'add' => 'Add Position',
        'import' => 'Import Positions',
        'download_sample' => 'Download Sample CSV',
        'delete' => 'Delete Position',
    ],

    'empty' => [
        'heading' => 'No positions found',
        'subtext' => 'Get started by creating your first position',
    ],

    'confirm_delete' => 'Are you sure you want to delete this position? This action cannot be undone.',
    'import_notes' => 'The CSV should contain a single header named name. Example rows are included in the sample file.',

    'import_errors' => [
        'unreadable' => 'The uploaded CSV could not be read.',
        'empty' => 'The CSV file is empty.',
        'no_rows' => 'The CSV must include at least one position row.',
        'invalid_headers' => 'The CSV must contain a single header named name.',
        'name_required' => 'Row :row: the position name is required.',
        'name_too_long' => 'Row :row: ":name" exceeds the 100 character limit.',
        'duplicate_in_file' => 'Row :row: ":name" appears more than once in the CSV.',
        'duplicate_existing' => 'Row :row: ":name" already exists.',
    ],
];
