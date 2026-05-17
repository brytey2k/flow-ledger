<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\FeatureFlag;
use App\Features\AdvancedReporting;
use App\Features\ApiAccess;
use App\Features\BulkExport;
use App\Features\MultiCurrency;
use PHPUnit\Framework\TestCase;

class FeatureFlagTest extends TestCase
{
    public function test_feature_class_returns_correct_class_for_each_case(): void
    {
        $this->assertSame(AdvancedReporting::class, FeatureFlag::AdvancedReporting->featureClass());
        $this->assertSame(BulkExport::class, FeatureFlag::BulkExport->featureClass());
        $this->assertSame(MultiCurrency::class, FeatureFlag::MultiCurrency->featureClass());
        $this->assertSame(ApiAccess::class, FeatureFlag::ApiAccess->featureClass());
    }

    public function test_label_returns_human_readable_name(): void
    {
        $this->assertSame('Advanced Reporting', FeatureFlag::AdvancedReporting->label());
        $this->assertSame('Bulk Export', FeatureFlag::BulkExport->label());
        $this->assertSame('Multi-Currency', FeatureFlag::MultiCurrency->label());
        $this->assertSame('API Access', FeatureFlag::ApiAccess->label());
    }

    public function test_cases_returns_all_four_flags(): void
    {
        $this->assertCount(4, FeatureFlag::cases());
    }
}
