<?php

declare(strict_types=1);

namespace App\Enums;

use App\Features\AdvancedReporting;
use App\Features\ApiAccess;
use App\Features\BulkExport;
use App\Features\MultiCurrency;

enum FeatureFlag: string
{
    case AdvancedReporting = 'advancedReporting';
    case BulkExport = 'bulkExport';
    case MultiCurrency = 'multiCurrency';
    case ApiAccess = 'apiAccess';

    public function featureClass(): string
    {
        return match ($this) {
            self::AdvancedReporting => AdvancedReporting::class,
            self::BulkExport => BulkExport::class,
            self::MultiCurrency => MultiCurrency::class,
            self::ApiAccess => ApiAccess::class,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::AdvancedReporting => 'Advanced Reporting',
            self::BulkExport => 'Bulk Export',
            self::MultiCurrency => 'Multi-Currency',
            self::ApiAccess => 'API Access',
        };
    }
}
