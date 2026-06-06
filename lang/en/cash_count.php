<?php

declare(strict_types=1);

return [
    'title' => 'Cash Count',
    'history_title' => 'Cash Count History',
    'create_title' => 'New Cash Count',
    'show_title' => 'Cash Count Result',

    'labels' => [
        'counted_at' => 'Counted At',
        'counted_by' => 'Counted By',
        'counted_total' => 'Counted Total',
        'balance_at_count' => 'Balance at Count',
        'difference' => 'Difference',
        'notes' => 'Notes',
        'denomination' => 'Denomination',
        'quantity' => 'Quantity',
        'subtotal' => 'Subtotal',
    ],

    'status' => [
        'equal' => 'Balanced',
        'surplus' => 'Surplus',
        'deficit' => 'Deficit',
    ],

    'empty' => [
        'title' => 'No cash counts yet',
        'description' => 'Perform a cash count to track physical cash against the cashbook balance.',
    ],

    'validation' => [
        'at_least_one_quantity' => 'At least one denomination must have a quantity greater than zero.',
    ],

    'buttons' => [
        'count_cash' => 'Count Cash',
        'view_history' => 'Count History',
        'new_count' => 'New Count',
        'back_to_history' => 'Back to History',
        'submit' => 'Record Count',
    ],

    'confirm_delete' => 'Are you sure you want to delete this cash count? This action cannot be undone.',

    'denominations' => [
        'title' => 'Denominations',
        'confirm_delete' => 'Are you sure you want to delete this denomination?',
        'add' => 'Add Denomination',
        'empty_title' => 'No denominations yet',
        'empty_description' => 'Add denominations for this currency to enable cash counting.',
        'labels' => [
            'value' => 'Value',
            'label' => 'Label',
            'sort_order' => 'Sort Order',
        ],
    ],
];
