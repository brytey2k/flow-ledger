<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Tenant;

use App\Enums\Tenant\PermissionKey;
use App\Models\Tenant\Branch;
use App\Models\Tenant\CashBalanceThreshold;
use App\Models\Tenant\Cashbook;
use App\Models\Tenant\Currency;
use App\Models\Tenant\PaymentRequest;
use App\Models\Tenant\Staff;
use Tests\ApiTenantTestCase;

class DashboardControllerTest extends ApiTenantTestCase
{
    public function test_returns_expected_structure(): void
    {
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
    }

    public function test_my_draft_requests_counts_only_current_user_drafts(): void
    {
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

        $this->assertSame(2, $response->json('data.my_draft_requests'));
        $this->assertSame(1, $response->json('data.my_in_workflow_requests'));
    }

    public function test_pending_disbursements_counts_approved_requests_in_branch_scope(): void
    {
        $currency = Currency::factory()->create();

        PaymentRequest::factory()->create([
            'staff_id' => Staff::factory()->create(['user_id' => $this->user->id, 'branch_id' => $this->branch->id])->id,
            'branch_id' => $this->branch->id,
            'currency_id' => $currency->id,
            'status' => 'approved',
        ]);

        $response = $this->getJson('/api/dashboard')->assertOk();

        $this->assertGreaterThanOrEqual(1, $response->json('data.pending_disbursements'));
    }

    public function test_low_cash_branches_hidden_without_settings_permission(): void
    {
        $this->role->revokePermissionTo(PermissionKey::AccessSettings->value);
        $this->user->unsetRelation('roles');
        $this->user->unsetRelation('permissions');

        $response = $this->getJson('/api/dashboard')->assertOk();

        $this->assertSame([], $response->json('data.low_cash_branches'));
    }

    public function test_low_cash_branches_shown_when_below_threshold(): void
    {
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
        $this->assertContains('Low Cash Branch', $names);
    }
}
