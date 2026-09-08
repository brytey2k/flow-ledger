<?php

declare(strict_types=1);

test('table footer cells use the same spacing as table content', function (): void {
    $contents = file_get_contents(dirname(__DIR__, 2) . '/resources/css/app.css');

    expect($contents)
        ->toBeString()
        ->toMatch(
            '/\.sgh-table-border thead th,\s*\.sgh-table-border tbody td,\s*\.sgh-table-border tfoot td\s*\{\s*@apply border-b border-border px-4 py-3 align-middle text-left;\s*\}/',
        );
});

test('approval total value is aligned with the amount column', function (): void {
    $contents = file_get_contents(dirname(__DIR__, 2) . '/resources/views/tenant/approvals/show.blade.php');

    expect($contents)
        ->toBeString()
        ->toMatch(
            '/<tfoot>\s*<tr>\s*<td colspan="2"[^>]*>.*?<\/td>\s*<td class="text-end">/s',
        );
});
