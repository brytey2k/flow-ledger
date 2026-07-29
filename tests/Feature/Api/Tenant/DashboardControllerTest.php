<?php

declare(strict_types=1);

uses(Tests\ApiTenantTestCase::class);
use App\Enums\Tenant\PermissionKey;
use App\Models\Tenant\Branch;
use App\Models\Tenant\CashBalanceThreshold;
use App\Models\Tenant\Cashbook;
use App\Models\Tenant\Currency;
use App\Models\Tenant\PaymentRequest;
use App\Models\Tenant\Staff;

test('returns expected structure', function () {
    $this->getJson('/api/dashboard')
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                'pending_approvals',
                'my_draft_requests',
                'my_in_workflow_requests',
                'my_draft_retirements',
                'pending_disbursements',
                'low_cash_branches',
            ],
        ]);
});
test('my draft requests counts only current user drafts', function () {
    $staff = Staff::factory()->create(['user_id' => $this->user->id, 'branch_id' => $this->branch->id]);
    $currency = Currency::factory()->create();

    PaymentRequest::factory()->create([
        'staff_id' => $staff->id,
        'branch_id' => $this->branch->id,
        'currency_id' => $currency->id,
        'status' => 'draft',
    ]);
    PaymentRequest::factory()->create([
        'staff_id' => $staff->id,
        'branch_id' => $this->branch->id,
        'currency_id' => $currency->id,
        'status' => 'draft',
    ]);
    PaymentRequest::factory()->create([
        'staff_id' => $staff->id,
        'branch_id' => $this->branch->id,
        'currency_id' => $currency->id,
        'status' => 'in_workflow',
    ]);

    $response = $this->getJson('/api/dashboard')->assertOk();

    expect($response->json('data.my_draft_requests'))->toBe(2);
    expect($response->json('data.my_in_workflow_requests'))->toBe(1);
});
test('pending disbursements counts approved requests in branch scope', function () {
    $currency = Currency::factory()->create();

    PaymentRequest::factory()->create([
        'staff_id' => Staff::factory()->create(['user_id' => $this->user->id, 'branch_id' => $this->branch->id])->id,
        'branch_id' => $this->branch->id,
        'currency_id' => $currency->id,
        'status' => 'approved',
    ]);

    $response = $this->getJson('/api/dashboard')->assertOk();

    expect($response->json('data.pending_disbursements'))->toBeGreaterThanOrEqual(1);
});
test('low cash branches hidden without settings permission', function () {
    $this->role->revokePermissionTo(PermissionKey::AccessSettings->value);
    $this->user->unsetRelation('roles');
    $this->user->unsetRelation('permissions');

    $response = $this->getJson('/api/dashboard')->assertOk();

    expect($response->json('data.low_cash_branches'))->toBe([]);
});
test('low cash branches shown when below threshold', function () {
    $currency = Currency::factory()->create();
    $branch = Branch::factory()->create([
        'name' => 'Low Cash Branch',
        'currency_id' => $currency->id,
        'level_id' => $this->level->id,
    ]);
    Cashbook::create([
        'branch_id' => $branch->id,
        'currency_id' => $currency->id,
        'balance' => 100.00,
    ]);
    CashBalanceThreshold::factory()->create([
        'branch_id' => $branch->id,
        'threshold_amount' => 1000.00,
    ]);

    $response = $this->getJson('/api/dashboard')->assertOk();

    $names = array_column($response->json('data.low_cash_branches'), 'name');
    expect($names)->toContain('Low Cash Branch');
});
