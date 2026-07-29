<?php

declare(strict_types=1);
use App\Enums\FeatureFlag;
use App\Features\DelegateIdentityToIdp;
use App\Features\LocalAuth;
use App\Features\MultiCurrency;
use App\Features\VerifyLoginWithIdp;

test('multi currency resolve returns false', function () {
    $feature = new MultiCurrency();
    expect($feature->resolve(null))->toBeFalse();
});
test('local auth resolve returns true', function () {
    $feature = new LocalAuth();
    expect($feature->resolve(null))->toBeTrue();
});
test('verify login with idp resolve returns false', function () {
    $feature = new VerifyLoginWithIdp();
    expect($feature->resolve(null))->toBeFalse();
});
test('delegate identity to idp resolve returns false', function () {
    $feature = new DelegateIdentityToIdp();
    expect($feature->resolve(null))->toBeFalse();
});
test('multi currency name matches enum', function () {
    $feature = new MultiCurrency();
    expect($feature->name)->toBe(FeatureFlag::MultiCurrency->value);
});
test('local auth name matches enum', function () {
    $feature = new LocalAuth();
    expect($feature->name)->toBe(FeatureFlag::LocalAuth->value);
});
test('verify login with idp name matches enum', function () {
    $feature = new VerifyLoginWithIdp();
    expect($feature->name)->toBe(FeatureFlag::VerifyLoginWithIdp->value);
});
test('delegate identity to idp name matches enum', function () {
    $feature = new DelegateIdentityToIdp();
    expect($feature->name)->toBe(FeatureFlag::DelegateIdentityToIdp->value);
});
