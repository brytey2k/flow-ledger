<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Tenant;
use Illuminate\Support\Facades\URL;
use Stancl\Tenancy\Contracts\TenancyBootstrapper;
use Stancl\Tenancy\Contracts\Tenant as TenantContract;

/**
 * Forces route()/url() generation to the tenant's own domain while tenancy
 * is bootstrapped, so links built outside an HTTP request (queued jobs,
 * artisan commands) resolve to the tenant instead of falling back to
 * config('app.url'), the central domain.
 */
class RootUrlTenancyBootstrapper implements TenancyBootstrapper
{
    private string|null $originalRootUrl = null;

    public function bootstrap(TenantContract $tenant): void
    {
        /** @var Tenant $tenant */
        $domain = $tenant->domains()->first()?->domain;

        if ($domain === null) {
            return;
        }

        $this->originalRootUrl = URL::to('/');

        $scheme = parse_url(config()->string('app.url'), PHP_URL_SCHEME) ?: 'http';

        URL::forceRootUrl("{$scheme}://{$domain}");
    }

    public function revert(): void
    {
        if ($this->originalRootUrl !== null) {
            URL::forceRootUrl($this->originalRootUrl);
        }

        $this->originalRootUrl = null;
    }
}
