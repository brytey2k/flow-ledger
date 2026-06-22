<?php

declare(strict_types=1);

namespace Tests\Unit\Features;

use App\Enums\FeatureFlag;
use App\Features\AdvancedReporting;
use App\Features\ApiAccess;
use App\Features\BulkExport;
use App\Features\DelegateIdentityToIdp;
use App\Features\LocalAuth;
use App\Features\MultiCurrency;
use App\Features\VerifyLoginWithIdp;
use PHPUnit\Framework\TestCase;

class FeatureClassesTest extends TestCase
{
    public function test_advanced_reporting_resolve_returns_false(): void
    {
        $feature = new AdvancedReporting();
        $this->assertFalse($feature->resolve(null));
    }

    public function test_bulk_export_resolve_returns_false(): void
    {
        $feature = new BulkExport();
        $this->assertFalse($feature->resolve(null));
    }

    public function test_multi_currency_resolve_returns_false(): void
    {
        $feature = new MultiCurrency();
        $this->assertFalse($feature->resolve(null));
    }

    public function test_api_access_resolve_returns_false(): void
    {
        $feature = new ApiAccess();
        $this->assertFalse($feature->resolve(null));
    }

    public function test_local_auth_resolve_returns_true(): void
    {
        $feature = new LocalAuth();
        $this->assertTrue($feature->resolve(null));
    }

    public function test_verify_login_with_idp_resolve_returns_false(): void
    {
        $feature = new VerifyLoginWithIdp();
        $this->assertFalse($feature->resolve(null));
    }

    public function test_delegate_identity_to_idp_resolve_returns_false(): void
    {
        $feature = new DelegateIdentityToIdp();
        $this->assertFalse($feature->resolve(null));
    }

    public function test_advanced_reporting_name_matches_enum(): void
    {
        $feature = new AdvancedReporting();
        $this->assertSame(FeatureFlag::AdvancedReporting->value, $feature->name);
    }

    public function test_bulk_export_name_matches_enum(): void
    {
        $feature = new BulkExport();
        $this->assertSame(FeatureFlag::BulkExport->value, $feature->name);
    }

    public function test_multi_currency_name_matches_enum(): void
    {
        $feature = new MultiCurrency();
        $this->assertSame(FeatureFlag::MultiCurrency->value, $feature->name);
    }

    public function test_api_access_name_matches_enum(): void
    {
        $feature = new ApiAccess();
        $this->assertSame(FeatureFlag::ApiAccess->value, $feature->name);
    }

    public function test_local_auth_name_matches_enum(): void
    {
        $feature = new LocalAuth();
        $this->assertSame(FeatureFlag::LocalAuth->value, $feature->name);
    }

    public function test_verify_login_with_idp_name_matches_enum(): void
    {
        $feature = new VerifyLoginWithIdp();
        $this->assertSame(FeatureFlag::VerifyLoginWithIdp->value, $feature->name);
    }

    public function test_delegate_identity_to_idp_name_matches_enum(): void
    {
        $feature = new DelegateIdentityToIdp();
        $this->assertSame(FeatureFlag::DelegateIdentityToIdp->value, $feature->name);
    }
}
