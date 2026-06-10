<?php

declare(strict_types=1);

namespace Tests\Feature\Dashboard;

use App\Enums\Tenant\PermissionKey;
use App\Models\Tenant\Branch;
use App\Models\Tenant\CashBalanceThreshold;
use App\Models\Tenant\Cashbook;
use App\Models\Tenant\Currency;
use Tests\TenantAppTestCase;

class DashboardTest extends TenantAppTestCase
{
    public function test_dashboard_shows_low_cash_balance_widget_for_branches_below_threshold(): void
    {
        $currency = Currency::factory()->create();
        $branch = Branch::factory()->create([
            'name' => 'Operations',
            'currency_id' => $currency->id,
            'level_id' => $this->level->id,
        ]);
        Cashbook::create([
            'branch_id' => $branch->id,
            'currency_id' => $currency->id,
            'balance' => 450.00,
        ]);
        CashBalanceThreshold::factory()->create([
            'branch_id' => $branch->id,
            'threshold_amount' => 1000.00,
            'notification_user_ids' => [$this->user->id],
        ]);

        $this->actingAs($this->user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee(__('cash_balance.alert_widget_title'))
            ->assertSee('Operations');
    }

    public function test_dashboard_hides_low_cash_balance_widget_for_users_without_settings_permission(): void
    {
        $this->role->revokePermissionTo(PermissionKey::AccessSettings->value);

        $currency = Currency::factory()->create();
        $branch = Branch::factory()->create([
            'name' => 'Operations',
            'currency_id' => $currency->id,
            'level_id' => $this->level->id,
        ]);
        Cashbook::create([
            'branch_id' => $branch->id,
            'currency_id' => $currency->id,
            'balance' => 450.00,
        ]);
        CashBalanceThreshold::factory()->create([
            'branch_id' => $branch->id,
            'threshold_amount' => 1000.00,
        ]);

        $this->actingAs($this->user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee(__('cash_balance.alert_widget_title'));
    }
}
