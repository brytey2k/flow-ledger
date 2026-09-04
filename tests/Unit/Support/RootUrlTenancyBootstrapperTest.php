<?php

declare(strict_types=1);

uses(Tests\TenantAppTestCase::class);

use App\Support\RootUrlTenancyBootstrapper;
use Illuminate\Support\Facades\URL;

test('bootstrap forces route generation onto the tenant domain', function () {
    $domain = $this->tenant->domains()->first()->domain;

    URL::forceRootUrl('http://flow-ledger.test');

    $bootstrapper = new RootUrlTenancyBootstrapper();
    $bootstrapper->bootstrap($this->tenant);

    expect(route('login'))->toStartWith("http://{$domain}/");
});

test('revert restores the root url that was active before bootstrapping', function () {
    URL::forceRootUrl('http://flow-ledger.test');

    $bootstrapper = new RootUrlTenancyBootstrapper();
    $bootstrapper->bootstrap($this->tenant);
    $bootstrapper->revert();

    expect(route('login'))->toStartWith('http://flow-ledger.test/');
});

test('bootstrap does nothing when the tenant has no domain', function () {
    $tenant = new App\Models\Tenant(['id' => 'no-domain-tenant']);

    URL::forceRootUrl('http://flow-ledger.test');

    $bootstrapper = new RootUrlTenancyBootstrapper();
    $bootstrapper->bootstrap($tenant);

    expect(route('login'))->toStartWith('http://flow-ledger.test/');
});
