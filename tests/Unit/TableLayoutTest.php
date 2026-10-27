<?php

declare(strict_types=1);

test('approval total value is aligned with the amount column', function (): void {
    $contents = file_get_contents(dirname(__DIR__, 2) . '/resources/views/tenant/approvals/show.blade.php');

    expect($contents)
        ->toBeString()
        ->toMatch(
            '/<tfoot>\s*<tr>\s*<td colspan="2"[^>]*>.*?<\/td>\s*<td class="text-end">/s',
        );
});
