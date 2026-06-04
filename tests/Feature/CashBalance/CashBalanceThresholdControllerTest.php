<?php

declare(strict_types=1);

namespace Tests\Feature\CashBalance;

use App\Enums\Tenant\PermissionKey;
use App\Models\Tenant\Branch;
use App\Models\Tenant\CashBalanceThreshold;
use App\Models\Tenant\Currency;
use App\Models\Tenant\User;
use Tests\TenantAppTestCase;

class CashBalanceThresholdControllerTest extends TenantAppTestCase
{
    public function test_guest_is_redirected_from_threshold_settings(): void
    {
        $this->get(route('cash-balance-thresholds.index'))
            ->assertRedirect(route('login'));
    }

    public function test_user_without_access_settings_cannot_view_threshold_settings(): void
    {
        $this->role->revokePermissionTo(PermissionKey::AccessSettings->value);

        $this->actingAs($this->user)
            ->get(route('cash-balance-thresholds.index'))
            ->assertForbidden();
    }

    public function test_user_can_view_threshold_settings(): void
    {
        $currency = Currency::factory()->create(['symbol' => '₵']);
        $branch = Branch::factory()->create([
            'currency_id' => $currency->id,
            'level_id' => $this->level->id,
        ]);
        CashBalanceThreshold::factory()->create([
            'branch_id' => $branch->id,
        ]);

        $this->actingAs($this->user)
            ->get(route('cash-balance-thresholds.index'))
            ->assertOk()
            ->assertSee(__('cash_balance.title'));
    }

    public function test_user_can_create_update_and_delete_thresholds(): void
    {
        $currency = Currency::factory()->create(['symbol' => '₵']);
        $branch = Branch::factory()->create([
            'currency_id' => $currency->id,
            'level_id' => $this->level->id,
        ]);
        $recipient = User::factory()->create([
            'branch_id' => $this->branch->id,
            'operational_branch_id' => $this->branch->id,
        ]);

        $this->actingAs($this->user)->post(route('cash-balance-thresholds.store'), [
            'branch_id' => $branch->id,
            'threshold_amount' => 2500.50,
            'notification_user_ids' => [$recipient->id],
            'cooldown_minutes' => 90,
            'is_active' => true,
        ])->assertRedirect(route('cash-balance-thresholds.index'));

        $threshold = CashBalanceThreshold::where('branch_id', $branch->id)->firstOrFail();
        $this->assertSame('2500.50', $threshold->threshold_amount);
        $this->assertSame([$recipient->id], $threshold->notification_user_ids);

        $this->actingAs($this->user)->put(route('cash-balance-thresholds.update', $threshold), [
            'threshold_amount' => 1200.00,
            'notification_user_ids' => [],
            'cooldown_minutes' => 30,
            'is_active' => false,
        ])->assertRedirect(route('cash-balance-thresholds.index'));

        $threshold->refresh();
        $this->assertSame('1200.00', $threshold->threshold_amount);
        $this->assertFalse($threshold->is_active);

        $this->actingAs($this->user)->delete(route('cash-balance-thresholds.destroy', $threshold))
            ->assertRedirect(route('cash-balance-thresholds.index'));

        $this->assertModelMissing($threshold);
    }
}
