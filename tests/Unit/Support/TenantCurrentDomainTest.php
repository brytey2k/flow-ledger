<?php

declare(strict_types=1);

uses(Tests\TenantAppTestCase::class);
use App\Models\Tenant\PaymentRequest;
use App\Notifications\RequestApprovedNotification;
use Illuminate\Http\Request;

test('tenant_current_domain falls back to the primary domain when there is no genuine tenant request', function () {
    // Test processes run via CLI, so Laravel binds a synthetic request derived
    // from config('app.url') — a central domain, not a real tenant request.
    expect(tenant_current_domain())->toBe($this->tenant->primary_domain_hostname);
});

test('tenant_current_domain prefers the actual request host over the primary domain', function () {
    $secondaryDomain = 'secondary.' . $this->tenant->id . '.test';
    $this->tenant->domains()->create(['domain' => $secondaryDomain, 'is_primary' => false]);

    app()->instance('request', Request::create("http://{$secondaryDomain}/dashboard"));

    // The tenant's primary domain is untouched — proving the helper picked
    // the domain the request actually came in on, not the primary fallback.
    expect(tenant_current_domain())
        ->toBe($secondaryDomain)
        ->not->toBe($this->tenant->primary_domain_hostname);
});

test('a notification built while a secondary tenant domain is the active request links back to that domain, not the primary one', function () {
    $secondaryDomain = 'secondary.' . $this->tenant->id . '.test';
    $this->tenant->domains()->create(['domain' => $secondaryDomain, 'is_primary' => false]);

    app()->instance('request', Request::create("http://{$secondaryDomain}/dashboard"));

    $paymentRequest = PaymentRequest::factory()->create();
    $notification = new RequestApprovedNotification($paymentRequest);

    expect($notification->domain)->toBe($secondaryDomain);

    $mail = $notification->toMail($this->user);

    $this->assertStringContainsString($secondaryDomain, $mail->actionUrl);
});
