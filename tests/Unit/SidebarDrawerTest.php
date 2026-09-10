<?php

declare(strict_types=1);

test('mobile drawer uses mutually exclusive display classes', function (string $sidebarView) {
    $contents = file_get_contents(dirname(__DIR__, 2) . '/' . $sidebarView);

    expect($contents)
        ->toBeString()
        ->toContain('x-cloak')
        ->toContain(":class=\"{ 'hidden': !\$store.sidebarDrawer.open, 'flex': \$store.sidebarDrawer.open");
})->with([
    'tenant sidebar' => ['resources/views/tenant/layouts/sidebar.blade.php'],
    'landlord sidebar' => ['resources/views/landlord/layouts/sidebar.blade.php'],
]);
