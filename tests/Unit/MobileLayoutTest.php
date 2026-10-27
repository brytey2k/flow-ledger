<?php

declare(strict_types=1);

test('phone input stacks before its fields can overflow a mobile viewport', function (): void {
    $contents = file_get_contents(dirname(__DIR__, 2) . '/resources/views/components/phone-input.blade.php');

    expect($contents)
        ->toBeString()
        ->toContain('class="flex flex-col gap-2 sm:flex-row"')
        ->toContain('class="sgh-select w-full sm:w-40 sm:shrink-0"')
        ->toContain('class="sgh-input min-w-0 w-full"')
        ->not->toContain('style="min-width:9rem');
});
