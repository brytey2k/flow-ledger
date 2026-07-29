<?php

declare(strict_types=1);
use App\Enums\FeatureFlag;
use App\Features\DelegateIdentityToIdp;
use App\Features\LocalAuth;
use App\Features\MultiCurrency;
use App\Features\VerifyLoginWithIdp;

test('feature class returns correct class for each case', function () {
    expect(FeatureFlag::MultiCurrency->featureClass())->toBe(MultiCurrency::class);
    expect(FeatureFlag::LocalAuth->featureClass())->toBe(LocalAuth::class);
    expect(FeatureFlag::VerifyLoginWithIdp->featureClass())->toBe(VerifyLoginWithIdp::class);
    expect(FeatureFlag::DelegateIdentityToIdp->featureClass())->toBe(DelegateIdentityToIdp::class);
});
test('label returns human readable name', function () {
    expect(FeatureFlag::MultiCurrency->label())->toBe('Multi-Currency');
    expect(FeatureFlag::LocalAuth->label())->toBe('Local Authentication');
    expect(FeatureFlag::VerifyLoginWithIdp->label())->toBe('Verify Login with IdP');
    expect(FeatureFlag::DelegateIdentityToIdp->label())->toBe('Delegate Identity to IdP');
});
test('cases returns all four flags', function () {
    expect(FeatureFlag::cases())->toHaveCount(4);
});
