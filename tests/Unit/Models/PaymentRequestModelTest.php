<?php

declare(strict_types=1);

uses(Tests\TenantAppTestCase::class);
use App\Models\Tenant\Branch;
use App\Models\Tenant\Cashbook;
use App\Models\Tenant\CashbookEntry;
use App\Models\Tenant\Currency;
use App\Models\Tenant\PaymentRequest;
use App\Models\Tenant\User;

test('is advance returns true for advance type', function () {
    $request = PaymentRequest::factory()->advance()->create(['status' => 'draft']);

    expect($request->isAdvance())->toBeTrue();
});
test('is advance returns false for expense type', function () {
    $request = PaymentRequest::factory()->expense()->create(['status' => 'draft']);

    expect($request->isAdvance())->toBeFalse();
});
test('is expense returns true for expense type', function () {
    $request = PaymentRequest::factory()->expense()->create(['status' => 'draft']);

    expect($request->isExpense())->toBeTrue();
});
test('is expense returns false for advance type', function () {
    $request = PaymentRequest::factory()->advance()->create(['status' => 'draft']);

    expect($request->isExpense())->toBeFalse();
});
test('is draft returns true when status is draft', function () {
    $request = PaymentRequest::factory()->advance()->create(['status' => 'draft']);

    expect($request->isDraft())->toBeTrue();
});
test('is draft returns false when status is not draft', function () {
    $request = PaymentRequest::factory()->advance()->create(['status' => 'in_workflow']);

    expect($request->isDraft())->toBeFalse();
});
test('is sent back returns true when status is sent back', function () {
    $request = PaymentRequest::factory()->advance()->create(['status' => 'sent_back']);

    expect($request->isSentBack())->toBeTrue();
});
test('is sent back returns false when status is not sent back', function () {
    $request = PaymentRequest::factory()->advance()->create(['status' => 'draft']);

    expect($request->isSentBack())->toBeFalse();
});
test('disbursed by relation loads user', function () {
    $user = User::factory()->create();
    $request = PaymentRequest::factory()->advance()->create([
        'status' => 'disbursed',
        'disbursed_at' => now(),
        'disbursed_by_user_id' => $user->id,
    ]);

    expect($request->disbursedBy?->id)->toEqual($user->id);
});
test('cashbook entries relation returns morph many', function () {
    $branch = Branch::factory()->create();
    $currency = Currency::factory()->create();
    $request = PaymentRequest::factory()->advance()->create(['status' => 'disbursed', 'disbursed_at' => now()]);
    $cashbook = Cashbook::create(['branch_id' => $branch->id, 'currency_id' => $currency->id, 'balance' => '0.00']);
    CashbookEntry::create([
        'cashbook_id' => $cashbook->id,
        'type' => 'debit',
        'amount' => '100.00',
        'description' => 'Payment',
        'entry_date' => now()->toDateString(),
        'sourceable_type' => PaymentRequest::class,
        'sourceable_id' => $request->id,
    ]);

    expect($request->cashbookEntries)->toHaveCount(1);
});
