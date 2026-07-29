<?php

declare(strict_types=1);

uses(Tests\TenantAppTestCase::class);
use App\DTOs\Tenant\CurrencyDenominationDto;
use App\Enums\Tenant\CurrencyDenominationType;
use App\Models\Tenant\Cashbook;
use App\Models\Tenant\CashCount;
use App\Models\Tenant\CashCountItem;
use App\Models\Tenant\Currency;
use App\Models\Tenant\CurrencyDenomination;
use App\Services\CurrencyDenominationService;

function makeServiceForCurrencyDenominationService(): CurrencyDenominationService
{
    return app(CurrencyDenominationService::class);
}
function makeCurrency(): Currency
{
    return Currency::factory()->create();
}
function makeDenominationForCurrencyDenominationService(Currency $currency, string $value = '10.00'): CurrencyDenomination
{
    return CurrencyDenomination::create([
        'currency_id' => $currency->id,
        'value' => $value,
        'label' => $value,
        'type' => 'note',
        'sort_order' => (int) round((float) $value),
    ]);
}
function makeCashbookForCurrencyDenominationService(Currency $currency): Cashbook
{
    return Cashbook::create([
        'branch_id' => test()->branch->id,
        'currency_id' => $currency->id,
        'balance' => '0.00',
    ]);
}
test('store returns currency denomination instance', function () {
    $currency = makeCurrency();
    $dto = new CurrencyDenominationDto(
        currencyId: $currency->id,
        value: '50.00',
        label: '50',
        type: CurrencyDenominationType::Note,
        sortOrder: 0,
    );

    $denomination = makeServiceForCurrencyDenominationService()->store($dto, $this->user);

    expect($denomination)->toBeInstanceOf(CurrencyDenomination::class);
});
test('store persists denomination with correct fields', function () {
    $currency = makeCurrency();
    $dto = new CurrencyDenominationDto(
        currencyId: $currency->id,
        value: '50.00',
        label: '50',
        type: CurrencyDenominationType::Note,
        sortOrder: 0,
    );

    $denomination = makeServiceForCurrencyDenominationService()->store($dto, $this->user);

    $persisted = CurrencyDenomination::findOrFail($denomination->id);
    expect($persisted->currency_id)->toBe($currency->id);
    expect((string) $persisted->value)->toBe('50.0000');
    expect($persisted->label)->toBe('50');
    expect($persisted->type)->toBe(CurrencyDenominationType::Note);
});
test('store sets sort order as rounded integer of value', function () {
    $currency = makeCurrency();
    $dto = new CurrencyDenominationDto(
        currencyId: $currency->id,
        value: '10.75',
        label: '10',
        type: CurrencyDenominationType::Note,
        sortOrder: 0,
    );

    $denomination = makeServiceForCurrencyDenominationService()->store($dto, $this->user);

    expect($denomination->sort_order)->toBe(11);
});
test('store sets sort order rounded down for values below half', function () {
    $currency = makeCurrency();
    $dto = new CurrencyDenominationDto(
        currencyId: $currency->id,
        value: '20.20',
        label: '20',
        type: CurrencyDenominationType::Coin,
        sortOrder: 0,
    );

    $denomination = makeServiceForCurrencyDenominationService()->store($dto, $this->user);

    expect($denomination->sort_order)->toBe(20);
});
test('store supports coin type', function () {
    $currency = makeCurrency();
    $dto = new CurrencyDenominationDto(
        currencyId: $currency->id,
        value: '0.50',
        label: '50p',
        type: CurrencyDenominationType::Coin,
        sortOrder: 0,
    );

    $denomination = makeServiceForCurrencyDenominationService()->store($dto, $this->user);

    expect($denomination->type)->toBe(CurrencyDenominationType::Coin);
});
test('update changes denomination label', function () {
    $currency = makeCurrency();
    $denomination = makeDenominationForCurrencyDenominationService($currency, '10.00');
    $dto = new CurrencyDenominationDto(
        currencyId: $currency->id,
        value: '10.00',
        label: 'Ten',
        type: CurrencyDenominationType::Note,
        sortOrder: 10,
    );

    makeServiceForCurrencyDenominationService()->update($denomination, $dto, $this->user);

    expect($denomination->fresh()->label)->toBe('Ten');
});
test('update changes denomination value', function () {
    $currency = makeCurrency();
    $denomination = makeDenominationForCurrencyDenominationService($currency, '10.00');
    $dto = new CurrencyDenominationDto(
        currencyId: $currency->id,
        value: '20.00',
        label: '20',
        type: CurrencyDenominationType::Note,
        sortOrder: 20,
    );

    makeServiceForCurrencyDenominationService()->update($denomination, $dto, $this->user);

    expect((string) $denomination->fresh()->value)->toBe('20.0000');
});
test('update changes denomination type', function () {
    $currency = makeCurrency();
    $denomination = makeDenominationForCurrencyDenominationService($currency, '5.00');
    $dto = new CurrencyDenominationDto(
        currencyId: $currency->id,
        value: '5.00',
        label: '5',
        type: CurrencyDenominationType::Coin,
        sortOrder: 5,
    );

    makeServiceForCurrencyDenominationService()->update($denomination, $dto, $this->user);

    expect($denomination->fresh()->type)->toBe(CurrencyDenominationType::Coin);
});
test('update changes denomination sort order', function () {
    $currency = makeCurrency();
    $denomination = makeDenominationForCurrencyDenominationService($currency, '10.00');
    $dto = new CurrencyDenominationDto(
        currencyId: $currency->id,
        value: '10.00',
        label: '10',
        type: CurrencyDenominationType::Note,
        sortOrder: 99,
    );

    makeServiceForCurrencyDenominationService()->update($denomination, $dto, $this->user);

    expect($denomination->fresh()->sort_order)->toBe(99);
});
test('delete removes denomination when no cash count items', function () {
    $currency = makeCurrency();
    $denomination = makeDenominationForCurrencyDenominationService($currency, '10.00');
    $denominationId = $denomination->id;

    makeServiceForCurrencyDenominationService()->delete($denomination, $this->user);

    expect(CurrencyDenomination::find($denominationId))->toBeNull();
});
test('delete throws logic exception when denomination has cash count items', function () {
    $currency = makeCurrency();
    $denomination = makeDenominationForCurrencyDenominationService($currency, '10.00');
    $cashbook = makeCashbookForCurrencyDenominationService($currency);

    $cashCount = CashCount::create([
        'cashbook_id' => $cashbook->id,
        'counted_by_user_id' => $this->user->id,
        'counted_at' => now(),
        'cashbook_balance_at_count' => '0.00',
        'counted_total' => '20.00',
        'difference' => '20.00',
    ]);

    CashCountItem::create([
        'cash_count_id' => $cashCount->id,
        'denomination_id' => $denomination->id,
        'denomination_value' => '10.00',
        'denomination_label' => '10',
        'quantity' => 2,
        'subtotal' => '20.00',
    ]);

    $this->expectException(LogicException::class);
    makeServiceForCurrencyDenominationService()->delete($denomination, $this->user);
});
test('delete does not remove denomination when it has cash count items', function () {
    $currency = makeCurrency();
    $denomination = makeDenominationForCurrencyDenominationService($currency, '10.00');
    $cashbook = makeCashbookForCurrencyDenominationService($currency);

    $cashCount = CashCount::create([
        'cashbook_id' => $cashbook->id,
        'counted_by_user_id' => $this->user->id,
        'counted_at' => now(),
        'cashbook_balance_at_count' => '0.00',
        'counted_total' => '20.00',
        'difference' => '20.00',
    ]);

    CashCountItem::create([
        'cash_count_id' => $cashCount->id,
        'denomination_id' => $denomination->id,
        'denomination_value' => '10.00',
        'denomination_label' => '10',
        'quantity' => 2,
        'subtotal' => '20.00',
    ]);

    try {
        makeServiceForCurrencyDenominationService()->delete($denomination, $this->user);
    } catch (LogicException) {
        // expected
    }

    expect(CurrencyDenomination::find($denomination->id))->not->toBeNull();
});
