<?php

declare(strict_types=1);

return [
    'title' => 'Cost Codes',
    'subtitle' => 'Manage cost codes and their department assignments',
    'import' => 'Import Cost Codes',
    'add_new' => 'Add New Cost Code',
    'all' => 'All Cost Codes',
    'create_title' => 'Create Cost Code',
    'create_subtitle' => 'Add a new cost code to your chart of accounts',
    'import_title' => 'Import Cost Codes',
    'import_subtitle' => 'Upload an XLSX file or download the sample template to bulk create cost codes.',
    'edit_title' => 'Edit Cost Code',
    'edit_subtitle' => 'Update cost code details',
    'back' => 'Back to Cost Codes',
    'details_card' => 'Cost Code Details',
    'import_card' => 'Import File',
    'sample_card' => 'XLSX Template',

    'fields' => [
        'code' => 'Code',
        'code_hint' => 'A unique identifier for this cost code.',
        'code_placeholder' => 'e.g. CC-1001',
        'name' => 'Name',
        'name_hint' => 'A descriptive name for this cost code.',
        'name_placeholder' => 'e.g. Office Supplies',
        'department' => 'Department',
        'select_department' => 'Select a department',
        'file' => 'XLSX File',
        'file_hint' => 'Upload an XLSX file with code, name, and department columns.',
    ],

    'buttons' => [
        'create' => 'Create Cost Code',
        'update' => 'Update Cost Code',
        'add' => 'Add Cost Code',
        'import' => 'Import Cost Codes',
        'download_sample' => 'Download Sample XLSX',
        'delete' => 'Delete Cost Code',
    ],

    'empty' => [
        'heading' => 'No cost codes found',
        'subtext' => 'Get started by creating your first cost code',
    ],

    'confirm_delete' => 'Are you sure you want to delete this cost code? This action cannot be undone.',
    'import_notes' => 'The XLSX must include code, name, and department columns. Department cells use a dropdown populated from your current departments.',

    'import_errors' => [
        'unreadable' => 'The uploaded XLSX could not be read.',
        'empty' => 'The XLSX file is empty.',
        'no_rows' => 'The XLSX must include at least one cost code row.',
        'invalid_headers' => 'The XLSX must contain the headers code, name, and department in that order.',
        'code_required' => 'Row :row: the cost code is required.',
        'code_too_long' => 'Row :row: ":code" exceeds the 50 character limit.',
        'name_required' => 'Row :row: the name is required.',
        'name_too_long' => 'Row :row: ":name" exceeds the 150 character limit.',
        'department_required' => 'Row :row: the department is required.',
        'department_invalid' => 'Row :row: ":department" is not a valid department.',
        'duplicate_in_file' => 'Row :row: ":code" appears more than once in the XLSX.',
        'duplicate_existing' => 'Row :row: ":code" already exists.',
    ],
];
