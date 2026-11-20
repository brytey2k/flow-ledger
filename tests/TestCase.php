<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        // As in TenantAppTestCase and LandlordTestCase: pages render without built assets, so the suite needs no
        // `npm run build` (CI does not run one).
        $this->withoutVite();
    }
}
