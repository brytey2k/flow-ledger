<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\FeatureFlag;
use App\Features\DelegateIdentityToIdp;
use App\Features\LocalAuth;
use App\Features\MultiCurrency;
use App\Features\VerifyLoginWithIdp;
use PHPUnit\Framework\TestCase;

class FeatureFlagTest extends TestCase
{
    public function test_feature_class_returns_correct_class_for_each_case(): void
    {
        $this->assertSame(MultiCurrency::class, FeatureFlag::MultiCurrency->featureClass());
        $this->assertSame(LocalAuth::class, FeatureFlag::LocalAuth->featureClass());
        $this->assertSame(VerifyLoginWithIdp::class, FeatureFlag::VerifyLoginWithIdp->featureClass());
        $this->assertSame(DelegateIdentityToIdp::class, FeatureFlag::DelegateIdentityToIdp->featureClass());
    }

    public function test_label_returns_human_readable_name(): void
    {
        $this->assertSame('Multi-Currency', FeatureFlag::MultiCurrency->label());
        $this->assertSame('Local Authentication', FeatureFlag::LocalAuth->label());
        $this->assertSame('Verify Login with IdP', FeatureFlag::VerifyLoginWithIdp->label());
        $this->assertSame('Delegate Identity to IdP', FeatureFlag::DelegateIdentityToIdp->label());
    }

    public function test_cases_returns_all_four_flags(): void
    {
        $this->assertCount(4, FeatureFlag::cases());
    }
}
