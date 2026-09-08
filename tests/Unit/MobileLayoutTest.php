<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

final class MobileLayoutTest extends TestCase
{
    public function test_shared_layout_contains_wide_content_without_page_level_overflow(): void
    {
        $contents = file_get_contents(dirname(__DIR__, 2) . '/resources/css/app.css');

        $this->assertIsString($contents);
        $this->assertMatchesRegularExpression('/html,\s*body\s*\{\s*@apply overflow-x-clip;\s*\}/', $contents);
        $this->assertMatchesRegularExpression('/\.sgh-shell\s*\{\s*@apply flex min-w-0 w-full grow;/', $contents);
        $this->assertMatchesRegularExpression('/\.sgh-wrapper\s*\{\s*@apply flex min-w-0 w-full grow flex-col;/', $contents);
        $this->assertMatchesRegularExpression('/\.sgh-scrollable-x-auto\s*\{\s*@apply min-w-0 max-w-full overflow-x-auto/', $contents);
    }

    public function test_phone_input_stacks_before_its_fields_can_overflow_a_mobile_viewport(): void
    {
        $contents = file_get_contents(dirname(__DIR__, 2) . '/resources/views/components/phone-input.blade.php');

        $this->assertIsString($contents);
        $this->assertStringContainsString('class="flex flex-col gap-2 sm:flex-row"', $contents);
        $this->assertStringContainsString('class="sgh-select w-full sm:w-40 sm:shrink-0"', $contents);
        $this->assertStringContainsString('class="sgh-input min-w-0 w-full"', $contents);
        $this->assertStringNotContainsString('style="min-width:9rem', $contents);
    }
}
