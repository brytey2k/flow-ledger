<?php

declare(strict_types=1);

namespace App\Enums;

use App\Features\DelegateIdentityToIdp;
use App\Features\LocalAuth;
use App\Features\MultiCurrency;
use App\Features\VerifyLoginWithIdp;

enum FeatureFlag: string
{
    case MultiCurrency = 'multiCurrency';
    case LocalAuth = 'localAuth';
    case VerifyLoginWithIdp = 'verifyLoginWithIdp';
    case DelegateIdentityToIdp = 'delegateIdentityToIdp';

    public function featureClass(): string
    {
        return match ($this) {
            self::MultiCurrency => MultiCurrency::class,
            self::LocalAuth => LocalAuth::class,
            self::VerifyLoginWithIdp => VerifyLoginWithIdp::class,
            self::DelegateIdentityToIdp => DelegateIdentityToIdp::class,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::MultiCurrency => 'Multi-Currency',
            self::LocalAuth => 'Local Authentication',
            self::VerifyLoginWithIdp => 'Verify Login with IdP',
            self::DelegateIdentityToIdp => 'Delegate Identity to IdP',
        };
    }
}
