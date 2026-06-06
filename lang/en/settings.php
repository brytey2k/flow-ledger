<?php

declare(strict_types=1);

return [
    'title' => 'Settings',
    'subtitle' => 'Manage your organisation\'s branding and system defaults.',

    'branding_card' => 'Branding',
    'advance_defaults_card' => 'Advance Defaults',
    'expense_settings_card' => 'Expense Settings',
    'retirement_settings_card' => 'Retirement Settings',
    'retirement_reminders_card' => 'Retirement Reminders',

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
        'retirement_reminder_grace_period_days' => 'Grace period (days)',
        'retirement_reminder_grace_period_days_hint' => 'Number of days after disbursement before the first overdue reminder is sent.',
        'retirement_reminder_frequency_days' => 'Reminder frequency (days)',
        'retirement_reminder_frequency_days_hint' => 'How often to repeat the reminder after the grace period has passed.',
        'retirement_reminder_notify_submitter' => 'Notify advance submitter',
        'retirement_reminder_notify_submitter_hint' => 'Send reminders to the staff member who submitted the advance.',
        'retirement_reminder_notify_approvers' => 'Notify workflow approvers',
        'retirement_reminder_notify_approvers_hint' => 'Send reminders to everyone who approved the advance workflow.',
        'retirement_reminder_notify_role_ids' => 'Also notify these roles',
        'retirement_reminder_notify_role_ids_hint' => 'Members of the selected roles will also receive reminders. Duplicate recipients are automatically suppressed.',
    ],
];
