<?php

declare(strict_types=1);

namespace App\Enums;

use App\Features\AdvancedReporting;
use App\Features\ApiAccess;
use App\Features\BulkExport;
use App\Features\DelegateIdentityToIdp;
use App\Features\LocalAuth;
use App\Features\MultiCurrency;
use App\Features\VerifyLoginWithIdp;

enum FeatureFlag: string
{
    case AdvancedReporting = 'advancedReporting';
    case BulkExport = 'bulkExport';
    case MultiCurrency = 'multiCurrency';
    case ApiAccess = 'apiAccess';
    case LocalAuth = 'localAuth';
    case VerifyLoginWithIdp = 'verifyLoginWithIdp';
    case DelegateIdentityToIdp = 'delegateIdentityToIdp';

    public function featureClass(): string
    {
        return match ($this) {
            self::AdvancedReporting => AdvancedReporting::class,
            self::BulkExport => BulkExport::class,
            self::MultiCurrency => MultiCurrency::class,
            self::ApiAccess => ApiAccess::class,
            self::LocalAuth => LocalAuth::class,
            self::VerifyLoginWithIdp => VerifyLoginWithIdp::class,
            self::DelegateIdentityToIdp => DelegateIdentityToIdp::class,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::AdvancedReporting => 'Advanced Reporting',
            self::BulkExport => 'Bulk Export',
            self::MultiCurrency => 'Multi-Currency',
            self::ApiAccess => 'API Access',
            self::LocalAuth => 'Local Authentication',
            self::VerifyLoginWithIdp => 'Verify Login with IdP',
            self::DelegateIdentityToIdp => 'Delegate Identity to IdP',
        };
    }
}
