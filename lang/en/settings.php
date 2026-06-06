<?php

declare(strict_types=1);

return [
    'title' => 'Settings',
    'subtitle' => 'Manage your organisation\'s branding and system defaults.',

    'branding_card' => 'Branding',
    'advance_defaults_card' => 'Advance Defaults',
    'expense_settings_card' => 'Expense Settings',
    'retirement_settings_card' => 'Retirement Settings',

    'fields' => [
        'logo' => 'Organisation Logo',
        'logo_hint' => 'Upload a PNG, JPG, or WebP image (max 2 MB). Recommended: wide aspect ratio, e.g. 272×44px.',
        'logo_preview_alt' => 'Current logo',
        'remove_logo' => 'Remove current logo',
        'default_advance_cost_code' => 'Default Cost Code for Advances',
        'default_advance_cost_code_hint' => 'This cost code is automatically applied when an advance is created without one selected.',
        'no_default_cost_code' => '— No default —',
        'require_expense_source_documents' => 'Require source documents for expenses',
        'require_expense_source_documents_hint' => 'When enabled, expense requests cannot be submitted without at least one source document (receipt, invoice, etc.) attached.',
        'require_retirement_source_documents' => 'Require source documents for retirements',
        'require_retirement_source_documents_hint' => 'When enabled, retirement requests cannot be submitted without at least one source document (receipt, invoice, etc.) attached.',
    ],
];
