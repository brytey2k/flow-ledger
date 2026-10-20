<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use App\Exceptions\ImmutableRecordException;
use Spatie\Activitylog\Models\Activity as BaseActivity;

/**
 * Append-only activity log entry. The application can write entries but never change or remove them.
 * Retention clean-up goes through the landlord data tool, which works on the table directly.
 */
class Activity extends BaseActivity
{
    protected static function booted(): void
    {
        static::updating(static function (): never {
            throw new ImmutableRecordException('Activity log entries cannot be edited.');
        });

        static::deleting(static function (): never {
            throw new ImmutableRecordException('Activity log entries cannot be deleted.');
        });
    }
}
