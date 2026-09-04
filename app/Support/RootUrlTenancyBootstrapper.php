<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Tenant;
use Illuminate\Routing\UrlGenerator;
use Illuminate\Support\Facades\URL;
use ReflectionProperty;
use Stancl\Tenancy\Contracts\TenancyBootstrapper;
use Stancl\Tenancy\Contracts\Tenant as TenantContract;

/**
 * Forces route()/url() generation to the tenant's own domain (and matching
 * scheme) while tenancy is bootstrapped, so links built outside an HTTP
 * request (queued jobs, artisan commands) resolve to the tenant instead of
 * falling back to the ambient request scheme or config('app.url'), the
 * central domain.
 *
 * The scheme must be forced explicitly rather than relying on the ambient
 * request: Laravel's UrlGenerator always derives the scheme from the current
 * request (or an explicit forceScheme()) independently of forceRootUrl()'s
 * scheme, so a bare forceRootUrl() call is not enough to guarantee the
 * tenant link uses the right scheme.
 */
class RootUrlTenancyBootstrapper implements TenancyBootstrapper
{
    private string|null $originalRoot = null;

    private string|null $originalScheme = null;

    private bool $bootstrapped = false;

    public function bootstrap(TenantContract $tenant): void
    {
        /** @var Tenant $tenant */
        $domain = $tenant->domains()->first()?->domain;

        if ($domain === null) {
            return;
        }

        $urlGenerator = app(UrlGenerator::class);
        $this->originalRoot = $this->readProtected($urlGenerator, 'forcedRoot');
        $forcedScheme = $this->readProtected($urlGenerator, 'forceScheme');
        $this->originalScheme = $forcedScheme === null ? null : rtrim($forcedScheme, ':/');
        $this->bootstrapped = true;

        $scheme = parse_url(config()->string('app.url'), PHP_URL_SCHEME) ?: 'http';

        URL::forceScheme($scheme);
        URL::useOrigin("{$scheme}://{$domain}");
    }

    public function revert(): void
    {
        if (! $this->bootstrapped) {
            return;
        }

        URL::forceScheme($this->originalScheme);
        URL::useOrigin($this->originalRoot);

        $this->bootstrapped = false;
        $this->originalRoot = null;
        $this->originalScheme = null;
    }

    private function readProtected(object $object, string $property): string|null
    {
        /** @var string|null $value */
        $value = (new ReflectionProperty($object, $property))->getValue($object);

        return $value;
    }
}
