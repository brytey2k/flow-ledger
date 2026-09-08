<?php

declare(strict_types=1);

test('shared layout contains wide content without page level overflow', function (): void {
    $contents = file_get_contents(dirname(__DIR__, 2) . '/resources/css/app.css');

    expect($contents)
        ->toBeString()
        ->toMatch('/html,\s*body\s*\{\s*@apply overflow-x-clip;\s*\}/')
        ->toMatch('/\.sgh-shell\s*\{\s*@apply flex min-w-0 w-full grow;/')
        ->toMatch('/\.sgh-wrapper\s*\{\s*@apply flex min-w-0 w-full grow flex-col;/')
        ->toMatch('/\.sgh-scrollable-x-auto\s*\{\s*@apply min-w-0 max-w-full overflow-x-auto/');
});

test('phone input stacks before its fields can overflow a mobile viewport', function (): void {
    $contents = file_get_contents(dirname(__DIR__, 2) . '/resources/views/components/phone-input.blade.php');

    expect($contents)
        ->toBeString()
        ->toContain('class="flex flex-col gap-2 sm:flex-row"')
        ->toContain('class="sgh-select w-full sm:w-40 sm:shrink-0"')
        ->toContain('class="sgh-input min-w-0 w-full"')
        ->not->toContain('style="min-width:9rem');
});
