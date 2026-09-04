<?php

declare(strict_types=1);

if (! function_exists('tenant_current_domain')) {
    /**
     * The domain to build tenant-facing links against: the domain the
     * current HTTP request came in on, or the tenant's primary domain when
     * there's no genuine tenant request to derive it from (queued jobs,
     * artisan commands — Laravel still binds a synthetic request derived
     * from config('app.url') in those contexts, so a bound request alone
     * isn't enough signal; it must also not be one of the central domains).
     *
     * Must be called synchronously while the request is still live (e.g. in
     * a queued notification's constructor) — by the time a queued job runs
     * on the worker, the originating request is gone.
     */
    function tenant_current_domain(): string
    {
        $host = request()->getHost();

        /** @var list<string> $centralDomains */
        $centralDomains = config('tenancy.central_domains', []);

        if ($host !== '' && ! in_array($host, $centralDomains, true)) {
            return $host;
        }

        /** @var App\Models\Tenant $tenant */
        $tenant = tenant();

        return $tenant->primary_domain_hostname;
    }
}

if (! function_exists('tenant_route_url')) {
    /**
     * Typed wrapper around Stancl\Tenancy's tenant_route(), which has no
     * type hints of its own.
     *
     * @param string $domain
     * @param string $route
     * @param mixed $parameters
     * @param bool $absolute
     */
    function tenant_route_url(string $domain, string $route, $parameters = [], bool $absolute = true): string
    {
        /** @var string $url */
        $url = tenant_route($domain, $route, $parameters, $absolute);

        return $url;
    }
}
