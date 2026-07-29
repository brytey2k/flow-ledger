<?php

declare(strict_types=1);

uses(Tests\TenantAppTestCase::class);
use App\Services\TenantImpersonationService;
use Illuminate\Support\Str;
use Stancl\Tenancy\Database\Models\ImpersonationToken;

test('exit impersonation requires authentication', function () {
    $this->post(route('exit-impersonation'))->assertRedirect(route('login'));
});
test('exit impersonation clears session and redirects to landlord', function () {
    $this->actingAs($this->user)
        ->withSession(['impersonated' => true])
        ->post(route('exit-impersonation'))
        ->assertRedirect(route('landlord.tenants.index'));

    $this->assertGuest();
});
test('exit impersonation removes impersonated session key', function () {
    $this->actingAs($this->user)
        ->withSession(['impersonated' => true])
        ->post(route('exit-impersonation'))
        ->assertSessionMissing('impersonated');
});
test('impersonate route sets session and logs in as tenant user', function () {
    $token = ImpersonationToken::create([
        'token' => Str::random(128),
        'tenant_id' => $this->tenant->getTenantKey(),
        'user_id' => (string) $this->user->id,
        'auth_guard' => 'web',
        'redirect_url' => '/dashboard',
        'created_at' => now(),
    ]);

    $this->get(route('impersonate', $token->token))
        ->assertRedirect('/dashboard')
        ->assertSessionHas('impersonated', true);

    $this->assertAuthenticatedAs($this->user);
});
test('impersonate default redirect url resolves to an existing route', function () {
    $token = app(TenantImpersonationService::class)->createImpersonationToken($this->tenant, $this->user);

    $this->get(route('impersonate', $token->token))
        ->assertRedirect(route('dashboard'))
        ->assertSessionHas('impersonated', true);

    $this->assertAuthenticatedAs($this->user);
});
