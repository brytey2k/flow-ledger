<?php

declare(strict_types=1);

namespace App\Notifications\Concerns;

/**
 * Captures the tenant domain a notification's links should point at, at
 * construction time — before the notification is queued and its originating
 * HTTP request is gone. See tenant_current_domain().
 */
trait CapturesTenantDomain
{
    public readonly string $domain;
}
