<?php

declare(strict_types=1);

uses(Tests\ApiTenantTestCase::class);
use App\Enums\Tenant\PermissionKey;
use App\Models\Tenant\Cashbook;
use App\Models\Tenant\Currency;
use App\Models\Tenant\Staff;

function initForCashbookController(): void
{
    test()->staff = Staff::factory()->create([
        'user_id' => test()->user->id,
        'branch_id' => test()->branch->id,
    ]);
    $currency = Currency::factory()->create();
    test()->cashbook = Cashbook::create([
        'branch_id' => test()->branch->id,
        'currency_id' => $currency->id,
        'balance' => 5000.00,
    ]);
}
beforeEach(function () {
    initForCashbookController();
});
test('index returns cashbook and entries', function () {
    $this->getJson('/api/cashbook')
        ->assertOk()
        ->assertJsonStructure([
            'data' => ['cashbook', 'entries'],
            'meta' => ['current_page', 'total'],
        ]);
});
test('index requires access cashbook permission', function () {
    $this->role->revokePermissionTo(PermissionKey::AccessCashbook->value);
    $this->user->unsetRelation('roles')->unsetRelation('permissions');

    $this->getJson('/api/cashbook')->assertForbidden();
});
test('index 422 when no staff profile', function () {
    $this->staff->delete();

    $this->getJson('/api/cashbook')->assertStatus(422);
});
test('store entry creates manual receipt', function () {
    $this->postJson('/api/cashbook/entries', [
        'amount' => 250.00,
        'entry_date' => today()->toDateString(),
        'reference' => 'RCPT-001',
    ])->assertCreated()
        ->assertJsonPath('data.amount', '250.00');
});
test('store entry requires amount and date', function () {
    $this->postJson('/api/cashbook/entries', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['amount', 'entry_date']);
});
test('store entry rejects future date', function () {
    $this->postJson('/api/cashbook/entries', [
        'amount' => 100.00,
        'entry_date' => today()->addDay()->toDateString(),
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['entry_date']);
});
test('store entry requires create cashbook entry permission', function () {
    $this->role->revokePermissionTo(PermissionKey::CreateCashbookEntry->value);
    $this->user->unsetRelation('roles')->unsetRelation('permissions');

    $this->postJson('/api/cashbook/entries', [
        'amount' => 100.00,
        'entry_date' => today()->toDateString(),
    ])->assertForbidden();
});
