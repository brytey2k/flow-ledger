<?php

declare(strict_types=1);

uses(Tests\TenantAppTestCase::class);
use App\Enums\Tenant\PermissionKey;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Cashbook;
use App\Models\Tenant\CashbookEntry;
use App\Models\Tenant\Currency;
use App\Models\Tenant\PaymentRequest;

function cashbookForBranch(Branch|null $branch = null, float $balance = 0): Cashbook
{
    $branch ??= test()->branch;
    $currency = Currency::factory()->create();

    return Cashbook::create([
        'branch_id' => $branch->id,
        'currency_id' => $currency->id,
        'balance' => $balance,
    ]);
}
/**
 * @param Cashbook $cashbook
 * @param float $amount
 * @param array<string, mixed> $overrides
 */
function manualEntry(Cashbook $cashbook, float $amount = 100.00, array $overrides = []): CashbookEntry
{
    return CashbookEntry::create(array_merge([
        'cashbook_id' => $cashbook->id,
        'type' => 'debit',
        'amount' => $amount,
        'description' => 'Manual receipt',
        'entry_date' => today(),
        'sourceable_type' => null,
        'sourceable_id' => null,
    ], $overrides));
}
function autoEntry(Cashbook $cashbook): CashbookEntry
{
    $paymentRequest = PaymentRequest::factory()->advance()->create(['branch_id' => test()->branch->id]);

    return CashbookEntry::create([
        'cashbook_id' => $cashbook->id,
        'type' => 'credit',
        'amount' => 200.00,
        'description' => 'Payment disbursed',
        'entry_date' => today(),
        'sourceable_type' => PaymentRequest::class,
        'sourceable_id' => $paymentRequest->id,
    ]);
}
test('guest is redirected from index', function () {
    $this->get(route('cashbook.index', $this->branch))
        ->assertRedirect(route('login'));
});
test('guest is redirected from create', function () {
    $this->get(route('cashbook.create', $this->branch))
        ->assertRedirect(route('login'));
});
test('guest cannot store receipt', function () {
    $this->post(route('cashbook.store', $this->branch), ['amount' => '100', 'entry_date' => today()->toDateString()])
        ->assertRedirect(route('login'));
});
test('guest cannot delete entry', function () {
    $cashbook = cashbookForBranch();
    $entry = manualEntry($cashbook);

    $this->delete(route('cashbook.destroy', [$this->branch, $entry]))
        ->assertRedirect(route('login'));
});
test('user without access permission cannot view index', function () {
    $this->role->revokePermissionTo(PermissionKey::AccessCashbook->value);

    $this->actingAs($this->user)
        ->get(route('cashbook.index', $this->branch))
        ->assertForbidden();
});
test('user without create permission cannot access create form', function () {
    $this->role->revokePermissionTo(PermissionKey::CreateCashbookEntry->value);

    $this->actingAs($this->user)
        ->get(route('cashbook.create', $this->branch))
        ->assertForbidden();
});
test('user without create permission cannot store receipt', function () {
    $this->role->revokePermissionTo(PermissionKey::CreateCashbookEntry->value);

    $this->actingAs($this->user)
        ->post(route('cashbook.store', $this->branch), ['amount' => '100', 'entry_date' => today()->toDateString()])
        ->assertForbidden();
});
test('user without delete permission cannot delete entry', function () {
    $this->role->revokePermissionTo(PermissionKey::DeleteCashbookEntry->value);
    $cashbook = cashbookForBranch();
    $entry = manualEntry($cashbook);

    $this->actingAs($this->user)
        ->delete(route('cashbook.destroy', [$this->branch, $entry]))
        ->assertForbidden();
});
test('authorised user sees cashbook index', function () {
    $cashbook = cashbookForBranch();
    manualEntry($cashbook, 250.00);

    $this->actingAs($this->user)
        ->get(route('cashbook.index', $this->branch))
        ->assertOk()
        ->assertViewIs('tenant.cashbook.index')
        ->assertViewHas('cashbook')
        ->assertViewHas('entries');
});
test('cashbook is auto created on first index visit', function () {
    $currency = Currency::factory()->create();
    $this->branch->update(['currency_id' => $currency->id]);

    expect(Cashbook::where('branch_id', $this->branch->id)->first())->toBeNull();

    $this->actingAs($this->user)
        ->get(route('cashbook.index', $this->branch))
        ->assertOk();

    expect(Cashbook::where('branch_id', $this->branch->id)->first())->not->toBeNull();
});
test('authorised user sees manual receipt form', function () {
    cashbookForBranch();

    $this->actingAs($this->user)
        ->get(route('cashbook.create', $this->branch))
        ->assertOk()
        ->assertViewIs('tenant.cashbook.create');
});
test('store creates debit entry and increments balance', function () {
    $cashbook = cashbookForBranch(balance: 500.00);

    $this->actingAs($this->user)
        ->post(route('cashbook.store', $this->branch), [
            'amount' => '150.00',
            'entry_date' => today()->toDateString(),
            'reference' => 'CHQ-001',
            'notes' => 'Bank top-up',
        ]);

    $this->assertDatabaseHas('cashbook_entries', [
        'cashbook_id' => $cashbook->id,
        'type' => 'debit',
        'sourceable_id' => null,
    ]);

    expect((float) $cashbook->fresh()->balance)->toEqualWithDelta(650.00, 0.01);
});
test('store redirects to cashbook index on success', function () {
    cashbookForBranch();

    $this->actingAs($this->user)
        ->post(route('cashbook.store', $this->branch), [
            'amount' => '100.00',
            'entry_date' => today()->toDateString(),
        ])
        ->assertRedirect(route('cashbook.index', $this->branch))
        ->assertSessionHas('success');
});
test('store reference and notes are optional', function () {
    $cashbook = cashbookForBranch();

    $this->actingAs($this->user)
        ->post(route('cashbook.store', $this->branch), [
            'amount' => '50.00',
            'entry_date' => today()->toDateString(),
        ])
        ->assertRedirect(route('cashbook.index', $this->branch));

    $this->assertDatabaseHas('cashbook_entries', [
        'cashbook_id' => $cashbook->id,
        'reference' => null,
        'notes' => null,
    ]);
});
test('amount is required', function () {
    cashbookForBranch();

    $this->actingAs($this->user)
        ->post(route('cashbook.store', $this->branch), [
            'entry_date' => today()->toDateString(),
        ])
        ->assertSessionHasErrors('amount');
});
test('amount must be greater than zero', function () {
    cashbookForBranch();

    $this->actingAs($this->user)
        ->post(route('cashbook.store', $this->branch), [
            'amount' => '0',
            'entry_date' => today()->toDateString(),
        ])
        ->assertSessionHasErrors('amount');
});
test('entry date is required', function () {
    cashbookForBranch();

    $this->actingAs($this->user)
        ->post(route('cashbook.store', $this->branch), [
            'amount' => '100.00',
        ])
        ->assertSessionHasErrors('entry_date');
});
test('entry date cannot be in the future', function () {
    cashbookForBranch();

    $this->actingAs($this->user)
        ->post(route('cashbook.store', $this->branch), [
            'amount' => '100.00',
            'entry_date' => today()->addDay()->toDateString(),
        ])
        ->assertSessionHasErrors('entry_date');
});
test('destroy deletes manual entry and decrements balance', function () {
    $cashbook = cashbookForBranch(balance: 400.00);
    $entry = manualEntry($cashbook, 150.00);

    $this->actingAs($this->user)
        ->delete(route('cashbook.destroy', [$this->branch, $entry]))
        ->assertRedirect(route('cashbook.index', $this->branch))
        ->assertSessionHas('success');

    $this->assertSoftDeleted('cashbook_entries', ['id' => $entry->id]);
    expect((float) $cashbook->fresh()->balance)->toEqualWithDelta(250.00, 0.01);
});
test('destroy cannot delete auto generated entry', function () {
    $cashbook = cashbookForBranch();
    $entry = autoEntry($cashbook);

    $this->actingAs($this->user)
        ->delete(route('cashbook.destroy', [$this->branch, $entry]))
        ->assertRedirect(route('cashbook.index', $this->branch))
        ->assertSessionHas('error');

    $this->assertDatabaseHas('cashbook_entries', ['id' => $entry->id, 'deleted_at' => null]);
});
test('filter by type returns only matching entries', function () {
    $cashbook = cashbookForBranch();
    $debit = manualEntry($cashbook, 100.00, ['type' => 'debit']);
    $credit = manualEntry($cashbook, 50.00, ['type' => 'credit']);

    $response = $this->actingAs($this->user)
        ->get(route('cashbook.index', array_merge(['branch' => $this->branch->id], ['type' => 'debit'])));

    $response->assertOk();
    $entries = $response->viewData('entries');
    $ids = $entries->pluck('id')->all();

    expect($ids)->toContain($debit->id);
    expect($ids)->not->toContain($credit->id);
});
test('filter by date range excludes out of range entries', function () {
    $cashbook = cashbookForBranch();
    $recent = manualEntry($cashbook, 100.00, ['entry_date' => today()]);
    $old = manualEntry($cashbook, 200.00, ['entry_date' => today()->subMonths(3)]);

    $response = $this->actingAs($this->user)
        ->get(route('cashbook.index', [
            'branch' => $this->branch->id,
            'date_from' => today()->subWeek()->toDateString(),
            'date_to' => today()->toDateString(),
        ]));

    $response->assertOk();
    $ids = $response->viewData('entries')->pluck('id')->all();

    expect($ids)->toContain($recent->id);
    expect($ids)->not->toContain($old->id);
});
test('filter by description matches description and notes', function () {
    $cashbook = cashbookForBranch();
    $match = manualEntry($cashbook, 100.00, ['description' => 'Bank top-up', 'notes' => null]);
    $noteMatch = manualEntry($cashbook, 75.00, ['description' => 'Receipt', 'notes' => 'Bank top-up transfer']);
    $noMatch = manualEntry($cashbook, 50.00, ['description' => 'Petty cash', 'notes' => null]);

    $response = $this->actingAs($this->user)
        ->get(route('cashbook.index', ['branch' => $this->branch->id, 'description' => 'Bank top-up']));

    $response->assertOk();
    $ids = $response->viewData('entries')->pluck('id')->all();

    expect($ids)->toContain($match->id);
    expect($ids)->toContain($noteMatch->id);
    expect($ids)->not->toContain($noMatch->id);
});
test('filter by amount range returns entries within bounds', function () {
    $cashbook = cashbookForBranch();
    $inRange = manualEntry($cashbook, 150.00);
    $tooSmall = manualEntry($cashbook, 50.00);
    $tooBig = manualEntry($cashbook, 500.00);

    $response = $this->actingAs($this->user)
        ->get(route('cashbook.index', [
            'branch' => $this->branch->id,
            'amount_min' => '100',
            'amount_max' => '200',
        ]));

    $response->assertOk();
    $ids = $response->viewData('entries')->pluck('id')->all();

    expect($ids)->toContain($inRange->id);
    expect($ids)->not->toContain($tooSmall->id);
    expect($ids)->not->toContain($tooBig->id);
});
test('combined filters narrow results', function () {
    $cashbook = cashbookForBranch();
    $match = manualEntry($cashbook, 200.00, ['type' => 'debit', 'entry_date' => today()]);
    $wrongType = manualEntry($cashbook, 200.00, ['type' => 'credit', 'entry_date' => today()]);
    $wrongDate = manualEntry($cashbook, 200.00, ['type' => 'debit', 'entry_date' => today()->subMonths(2)]);

    $response = $this->actingAs($this->user)
        ->get(route('cashbook.index', [
            'branch' => $this->branch->id,
            'type' => 'debit',
            'date_from' => today()->subWeek()->toDateString(),
        ]));

    $response->assertOk();
    $ids = $response->viewData('entries')->pluck('id')->all();

    expect($ids)->toContain($match->id);
    expect($ids)->not->toContain($wrongType->id);
    expect($ids)->not->toContain($wrongDate->id);
});
test('guest cannot export cashbook', function () {
    $this->get(route('cashbook.export', $this->branch))
        ->assertRedirect(route('login'));
});
test('user without access permission cannot export', function () {
    $this->role->revokePermissionTo(PermissionKey::AccessCashbook->value);

    $this->actingAs($this->user)
        ->get(route('cashbook.export', $this->branch))
        ->assertForbidden();
});
test('export returns csv download', function () {
    $cashbook = cashbookForBranch();
    manualEntry($cashbook, 100.00);

    $response = $this->actingAs($this->user)
        ->get(route('cashbook.export', $this->branch));

    $response->assertOk();
    $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type') ?? '');
    $this->assertStringContainsString('attachment', $response->headers->get('Content-Disposition') ?? '');
    $this->assertStringContainsString('.csv', $response->headers->get('Content-Disposition') ?? '');
});
test('export includes correct column headers', function () {
    $cashbook = cashbookForBranch();

    $response = $this->actingAs($this->user)
        ->get(route('cashbook.export', $this->branch));

    $response->assertOk();
    $content = $response->streamedContent();
    $this->assertStringContainsString('Date', $content);
    $this->assertStringContainsString('Description', $content);
    $this->assertStringContainsString('Reference', $content);
    $this->assertStringContainsString('Type', $content);
    $this->assertStringContainsString('Amount', $content);
    $this->assertStringContainsString('Notes', $content);
});
test('export respects type filter', function () {
    $cashbook = cashbookForBranch();
    manualEntry($cashbook, 100.00, ['type' => 'debit', 'description' => 'Debit entry']);
    manualEntry($cashbook, 50.00, ['type' => 'credit', 'description' => 'Credit entry']);

    $response = $this->actingAs($this->user)
        ->get(route('cashbook.export', array_merge(['branch' => $this->branch->id], ['type' => 'debit'])));

    $response->assertOk();
    $content = $response->streamedContent();

    $this->assertStringContainsString('Debit entry', $content);
    $this->assertStringNotContainsString('Credit entry', $content);
});
test('export neutralises formula injection in free text fields', function () {
    $cashbook = cashbookForBranch();
    manualEntry($cashbook, 100.00, [
        'description' => '=HYPERLINK("http://evil.test","click")',
        'reference' => '+cmd|"/c calc"',
        'notes' => '@SUM(1+1)',
    ]);

    $response = $this->actingAs($this->user)
        ->get(route('cashbook.export', $this->branch));

    $response->assertOk();
    $content = $response->streamedContent();

    $this->assertStringContainsString('"\'=HYPERLINK(""http://evil.test"",""click"")"', $content);
    $this->assertStringContainsString('"\'+cmd|""/c calc"""', $content);
    $this->assertStringContainsString('\'@SUM(1+1)', $content);
});
