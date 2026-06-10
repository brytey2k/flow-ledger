<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\CashBalanceThresholdStoreRequest;
use App\Http\Requests\Tenant\CashBalanceThresholdUpdateRequest;
use App\Models\Tenant\Branch;
use App\Models\Tenant\CashBalanceThreshold;
use App\Models\Tenant\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CashBalanceThresholdController extends Controller
{
    public function index(): View
    {
        $branches = Branch::with(['cashbook.currency', 'cashBalanceThreshold'])
            ->orderBy('position')
            ->get();
        $thresholds = CashBalanceThreshold::with(['branch.currency', 'notificationLogs'])
            ->orderBy('branch_id')
            ->get();

        $users = User::query()->orderBy('first_name')->orderBy('last_name')->get();

        return view('tenant.settings.cash-balance-thresholds', compact('branches', 'thresholds', 'users'));
    }

    public function searchUsers(Request $request): JsonResponse
    {
        $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
        ]);

        $q = $request->string('q')->toString();

        $users = User::query()
            ->when($q !== '', fn($query) => $query->where(
                fn($sub) => $sub
                    ->where('first_name', 'like', "%{$q}%")
                    ->orWhere('last_name', 'like', "%{$q}%"),
            ))
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->limit(20)
            ->get()
            ->map(fn($user) => [
                'id' => $user->id,
                'text' => $user->first_name . ' ' . $user->last_name,
            ])
            ->all();

        return response()->json($users);
    }

    public function store(CashBalanceThresholdStoreRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        CashBalanceThreshold::updateOrCreate(
            ['branch_id' => $validated['branch_id']],
            [
                'threshold_amount' => $validated['threshold_amount'],
                'notification_user_ids' => $validated['notification_user_ids'] ?? [],
                'cooldown_minutes' => $validated['cooldown_minutes'],
                'is_active' => $validated['is_active'] ?? true,
            ],
        );

        return redirect()->route('cash-balance-thresholds.index')
            ->with('success', __('flash.cash_balance.threshold_saved'));
    }

    public function update(CashBalanceThresholdUpdateRequest $request, CashBalanceThreshold $threshold): RedirectResponse
    {
        $validated = $request->validated();

        $threshold->update([
            'threshold_amount' => $validated['threshold_amount'],
            'notification_user_ids' => $validated['notification_user_ids'] ?? [],
            'cooldown_minutes' => $validated['cooldown_minutes'],
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return redirect()->route('cash-balance-thresholds.index')
            ->with('success', __('flash.cash_balance.threshold_updated'));
    }

    public function destroy(CashBalanceThreshold $threshold): RedirectResponse
    {
        $threshold->delete();

        return redirect()->route('cash-balance-thresholds.index')
            ->with('success', __('flash.cash_balance.threshold_deleted'));
    }
}
