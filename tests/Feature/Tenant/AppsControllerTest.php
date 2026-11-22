<?php

declare(strict_types=1);

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

uses(Tests\TenantAppTestCase::class);

beforeEach(function () {
    config([
        'sso.product_slug' => 'flow-ledger',
        'sso.idp_internal_url' => 'https://idp.test',
        'sso.m2m_client_id' => 'm2m-client',
        'sso.m2m_client_secret' => 'm2m-secret',
        'sso.m2m_apps_scope' => 'apps:read',
    ]);
});

/**
 * @param list<array<string, mixed>> $apps
 */
function fakeIdpAppList(array $apps): void
{
    Http::fake([
        'https://idp.test/oauth/token' => Http::response(['access_token' => 'apps-token', 'expires_in' => 3600]),
        'https://idp.test/api/m2m/apps' => Http::response(['data' => $apps]),
    ]);
}

test('guests cannot read the app list', function () {
    Http::fake();

    $this->getJson(route('apps.index'))->assertUnauthorized();

    Http::assertNothingSent();
});

test('it returns the other apps with homepage and sign in urls', function () {
    fakeIdpAppList([
        ['name' => 'Accounting', 'slug' => 'accounting', 'sso_redirect_url' => 'https://accounting.test/auth/sso/redirect', 'marketing_page_url' => 'https://accounting.example.com'],
        ['name' => 'Flow Ledger', 'slug' => 'flow-ledger', 'sso_redirect_url' => 'https://flow-ledger.test/auth/sso/redirect', 'marketing_page_url' => 'https://flow-ledger.example.com'],
        ['name' => 'Steward', 'slug' => 'church', 'sso_redirect_url' => 'https://church.test/auth/sso/redirect', 'marketing_page_url' => null],
    ]);

    $this->actingAs($this->user)
        ->getJson(route('apps.index'))
        ->assertOk()
        ->assertExactJson(['data' => [
            ['name' => 'Accounting', 'homepage_url' => 'https://accounting.example.com', 'sign_in_url' => 'https://accounting.test/auth/sso/redirect'],
            ['name' => 'Steward', 'homepage_url' => null, 'sign_in_url' => 'https://church.test/auth/sso/redirect'],
        ]]);

    Http::assertSent(static fn(Request $request): bool => $request->url() === 'https://idp.test/oauth/token'
        && $request['grant_type'] === 'client_credentials'
        && $request['client_id'] === 'm2m-client'
        && $request['scope'] === 'apps:read');
    Http::assertSent(static fn(Request $request): bool => $request->url() === 'https://idp.test/api/m2m/apps'
        && $request->hasHeader('Authorization', 'Bearer apps-token'));
});

test('it drops apps without a usable sign in url and blanks unsafe homepage urls', function () {
    fakeIdpAppList([
        ['name' => 'Scripted', 'slug' => 'scripted', 'sso_redirect_url' => 'javascript:alert(1)', 'marketing_page_url' => 'https://scripted.example.com'],
        ['name' => 'Missing', 'slug' => 'missing'],
        ['name' => 'Attendance', 'slug' => 'attendance', 'sso_redirect_url' => 'https://attendance.test/auth/sso/redirect', 'marketing_page_url' => 'javascript:alert(1)'],
        'not-an-app',
    ]);

    $this->actingAs($this->user)
        ->getJson(route('apps.index'))
        ->assertOk()
        ->assertExactJson(['data' => [
            ['name' => 'Attendance', 'homepage_url' => null, 'sign_in_url' => 'https://attendance.test/auth/sso/redirect'],
        ]]);
});

test('it serves repeat requests from cache', function () {
    fakeIdpAppList([
        ['name' => 'Accounting', 'slug' => 'accounting', 'sso_redirect_url' => 'https://accounting.test/auth/sso/redirect', 'marketing_page_url' => null],
    ]);

    $this->actingAs($this->user)->getJson(route('apps.index'))->assertOk()->assertJsonCount(1, 'data');
    $this->actingAs($this->user)->getJson(route('apps.index'))->assertOk()->assertJsonCount(1, 'data');

    Http::assertSentCount(2);
});

test('it returns bad gateway when the idp app list request fails', function () {
    Http::fake([
        'https://idp.test/oauth/token' => Http::response(['access_token' => 'apps-token', 'expires_in' => 3600]),
        'https://idp.test/api/m2m/apps' => Http::response(['message' => 'boom'], 500),
    ]);

    $this->actingAs($this->user)
        ->getJson(route('apps.index'))
        ->assertStatus(502)
        ->assertJsonPath('message', __('navigation.apps_unavailable'));
});

test('it returns bad gateway when the idp refuses the m2m token', function () {
    Http::fake([
        'https://idp.test/oauth/token' => Http::response(['error' => 'invalid_scope'], 400),
    ]);

    $this->actingAs($this->user)->getJson(route('apps.index'))->assertStatus(502);

    Http::assertNotSent(static fn(Request $request): bool => $request->url() === 'https://idp.test/api/m2m/apps');
});

test('it returns bad gateway when the idp cannot be reached', function () {
    Http::fake(['https://idp.test/*' => Http::failedConnection()]);

    $this->actingAs($this->user)->getJson(route('apps.index'))->assertStatus(502);
});

test('it returns bad gateway when the idp returns a malformed app list', function () {
    Http::fake([
        'https://idp.test/oauth/token' => Http::response(['access_token' => 'apps-token', 'expires_in' => 3600]),
        'https://idp.test/api/m2m/apps' => Http::response(['apps' => []]),
    ]);

    $this->actingAs($this->user)->getJson(route('apps.index'))->assertStatus(502);
});

test('it returns bad gateway when m2m credentials are not configured', function () {
    config(['sso.m2m_client_id' => '', 'sso.m2m_client_secret' => '']);
    Http::fake();

    $this->actingAs($this->user)->getJson(route('apps.index'))->assertStatus(502);

    Http::assertNothingSent();
});

test('the header renders the apps drawer without calling the idp', function () {
    Http::fake();

    $this->actingAs($this->user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('aria-label="' . __('navigation.apps') . '"', false)
        ->assertSee('data-apps-url="' . route('apps.index') . '"', false);

    Http::assertNothingSent();
});
