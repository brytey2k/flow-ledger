<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class SidebarDrawerTest extends TestCase
{
    #[DataProvider('sidebarViewProvider')]
    public function test_mobile_drawer_uses_mutually_exclusive_display_classes(string $sidebarView): void
    {
        $contents = file_get_contents(dirname(__DIR__, 2) . '/' . $sidebarView);

        $this->assertIsString($contents);
        $this->assertStringContainsString('x-cloak', $contents);
        $this->assertStringContainsString(
            ":class=\"{ 'hidden': !\$store.sidebarDrawer.open, 'flex': \$store.sidebarDrawer.open",
            $contents,
        );
    }

    /** @return array<string, array{string}> */
    public static function sidebarViewProvider(): array
    {
        return [
            'tenant sidebar' => ['resources/views/tenant/layouts/sidebar.blade.php'],
            'landlord sidebar' => ['resources/views/landlord/layouts/sidebar.blade.php'],
        ];
    }
}
