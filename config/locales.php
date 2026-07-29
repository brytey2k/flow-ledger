<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Supported Locales
    |--------------------------------------------------------------------------
    |
    | The single source of truth for which locales the application offers in
    | language switchers, and whether each one renders right-to-left. Add a
    | new locale here and it becomes selectable everywhere without touching
    | controller code.
    |
    */
    'supported' => [
        'en' => [
            'native_label' => 'English',
            'rtl' => false,
        ],
        'fr' => [
            'native_label' => 'Français',
            'rtl' => false,
        ],
    ],
];
