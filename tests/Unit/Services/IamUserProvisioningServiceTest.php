<?php

declare(strict_types=1);

uses(Tests\TenantAppTestCase::class);
use App\Models\Tenant\User;
use App\Services\IamUserProvisioningService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

const IAM_USER_PROVISIONING_SERVICE_IDP_INTERNAL_URL = 'http://iam-internal.test:82';
beforeEach(function () {
    config(['cache.default' => 'array']);
    Cache::flush();

    config([
        'sso.idp_internal_url' => IAM_USER_PROVISIONING_SERVICE_IDP_INTERNAL_URL,
        'sso.m2m_client_id' => 'flow-ledger-m2m-client',
        'sso.m2m_client_secret' => 'flow-ledger-m2m-secret',
        'sso.m2m_invite_scope' => 'users:invite',
        'sso.product_slug' => 'flow-ledger',
        'sso.verify_ssl' => false,
    ]);

    $this->service = new IamUserProvisioningService();
});
test('invite sends bearer token and payload', function () {
    $user = User::factory()->create([
        'branch_id' => $this->branch->id,
        'email' => 'invitee@example.com',
        'first_name' => 'Jane',
        'last_name' => 'Doe',
    ]);

    Http::fake([
        IAM_USER_PROVISIONING_SERVICE_IDP_INTERNAL_URL . '/oauth/token' => Http::response(['access_token' => 'm2m-token', 'expires_in' => 300], 200),
        IAM_USER_PROVISIONING_SERVICE_IDP_INTERNAL_URL . '/api/m2m/tenants/users/invite' => Http::response(['user_id' => 'iam-user-1', 'status' => 'created'], 200),
    ]);

    $result = $this->service->invite($user, 'idp-tenant-1');

    expect($result)->toBe(['iam_user_id' => 'iam-user-1', 'status' => 'created']);

    Http::assertSent(static fn($request): bool => $request->url() === IAM_USER_PROVISIONING_SERVICE_IDP_INTERNAL_URL . '/oauth/token'
            && $request['grant_type'] === 'client_credentials'
            && $request['scope'] === 'users:invite');

    Http::assertSent(static fn($request): bool => $request->url() === IAM_USER_PROVISIONING_SERVICE_IDP_INTERNAL_URL . '/api/m2m/tenants/users/invite'
            && $request->hasHeader('Authorization', 'Bearer m2m-token')
            && $request['product_slug'] === 'flow-ledger'
            && $request['idp_tenant_id'] === 'idp-tenant-1'
            && $request['email'] === 'invitee@example.com'
            && $request['first_name'] === 'Jane'
            && $request['last_name'] === 'Doe');
});
test('invite throws when invite request fails', function () {
    $user = User::factory()->create(['branch_id' => $this->branch->id]);

    Http::fake([
        IAM_USER_PROVISIONING_SERVICE_IDP_INTERNAL_URL . '/oauth/token' => Http::response(['access_token' => 'm2m-token', 'expires_in' => 300], 200),
        IAM_USER_PROVISIONING_SERVICE_IDP_INTERNAL_URL . '/api/m2m/tenants/users/invite' => Http::response(['error' => 'server error'], 500),
    ]);

    $this->expectException(RuntimeException::class);
    $this->expectExceptionMessage('IDP user invite failed');

    $this->service->invite($user, 'idp-tenant-1');
});
test('invite throws when invite response missing user id', function () {
    $user = User::factory()->create(['branch_id' => $this->branch->id]);

    Http::fake([
        IAM_USER_PROVISIONING_SERVICE_IDP_INTERNAL_URL . '/oauth/token' => Http::response(['access_token' => 'm2m-token', 'expires_in' => 300], 200),
        IAM_USER_PROVISIONING_SERVICE_IDP_INTERNAL_URL . '/api/m2m/tenants/users/invite' => Http::response(['status' => 'created'], 200),
    ]);

    $this->expectException(RuntimeException::class);
    $this->expectExceptionMessage('IDP user invite response was invalid');

    $this->service->invite($user, 'idp-tenant-1');
});
test('invite accepts an integer user id, as IAM actually returns', function () {
    $user = User::factory()->create(['branch_id' => $this->branch->id]);

    Http::fake([
        IAM_USER_PROVISIONING_SERVICE_IDP_INTERNAL_URL . '/oauth/token' => Http::response(['access_token' => 'm2m-token', 'expires_in' => 300], 200),
        IAM_USER_PROVISIONING_SERVICE_IDP_INTERNAL_URL . '/api/m2m/tenants/users/invite' => Http::response(['user_id' => 42, 'status' => 'created'], 200),
    ]);

    $result = $this->service->invite($user, 'idp-tenant-1');

    expect($result)->toBe(['iam_user_id' => '42', 'status' => 'created']);
});
test('invite treats existing iam identity as exists status', function () {
    $user = User::factory()->create(['branch_id' => $this->branch->id]);

    Http::fake([
        IAM_USER_PROVISIONING_SERVICE_IDP_INTERNAL_URL . '/oauth/token' => Http::response(['access_token' => 'm2m-token', 'expires_in' => 300], 200),
        IAM_USER_PROVISIONING_SERVICE_IDP_INTERNAL_URL . '/api/m2m/tenants/users/invite' => Http::response(['user_id' => 'iam-user-existing', 'status' => 'exists'], 200),
    ]);

    $result = $this->service->invite($user, 'idp-tenant-1');

    expect($result)->toBe(['iam_user_id' => 'iam-user-existing', 'status' => 'exists']);
});
test('invite throws when token request fails', function () {
    $user = User::factory()->create(['branch_id' => $this->branch->id]);

    Http::fake([
        IAM_USER_PROVISIONING_SERVICE_IDP_INTERNAL_URL . '/oauth/token' => Http::response(['error' => 'invalid_client'], 401),
    ]);

    $this->expectException(RuntimeException::class);
    $this->expectExceptionMessage('IDP client token request failed');

    $this->service->invite($user, 'idp-tenant-1');
});
test('invite reuses cached client credentials token', function () {
    $userOne = User::factory()->create(['branch_id' => $this->branch->id]);
    $userTwo = User::factory()->create(['branch_id' => $this->branch->id]);

    Http::fake([
        IAM_USER_PROVISIONING_SERVICE_IDP_INTERNAL_URL . '/oauth/token' => Http::response(['access_token' => 'm2m-token', 'expires_in' => 300], 200),
        IAM_USER_PROVISIONING_SERVICE_IDP_INTERNAL_URL . '/api/m2m/tenants/users/invite' => Http::response(['user_id' => 'iam-user-1', 'status' => 'created'], 200),
    ]);

    $this->service->invite($userOne, 'idp-tenant-1');
    $this->service->invite($userTwo, 'idp-tenant-1');

    Http::assertSentCount(3);

    expect(Cache::has('idp.m2m.token.' . sha1('flow-ledger-m2m-client|users:invite')))->toBeTrue();
});
