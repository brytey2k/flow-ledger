<?php

declare(strict_types=1);

return [
    'title' => 'Activity Log',
    'subtitle' => 'Audit trail of all actions performed in the system',

    'filters' => [
        'heading' => 'Filters',
        'subject_type' => 'Subject Type',
        'all_types' => 'All types',
        'user' => 'User',
        'staff' => 'Staff',
        'payment_request' => 'Payment Request',
        'retirement_request' => 'Retirement Request',
        'role' => 'Role',
        'branch' => 'Branch',
        'event' => 'Event',
        'event_placeholder' => 'e.g. user.created',
        'performed_by' => 'Performed By',
        'performed_by_placeholder' => 'Name or email…',
    ],

    'entries_heading' => 'Entries',
    'total' => 'total',
    'no_entries' => 'No activity log entries found.',

    'show' => [
        'title' => 'Activity Log Entry',
        'details_card' => 'Details',
        'properties' => 'Properties',
        'no_properties' => 'No additional properties recorded.',
    ],
];
