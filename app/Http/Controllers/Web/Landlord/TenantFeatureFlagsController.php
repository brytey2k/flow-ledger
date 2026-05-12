<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Landlord;

use App\Enums\FeatureFlag;
use App\Http\Controllers\Controller;
use App\Http\Requests\Landlord\BulkFeatureFlagUpdateRequest;
use App\Http\Requests\Landlord\TenantFeatureFlagUpdateRequest;
use App\Interfaces\FeatureFlagServiceInterface;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TenantFeatureFlagsController extends Controller
{
    public function __construct(private readonly FeatureFlagServiceInterface $featureFlagService) {}

    public function overview(): View
    {
        $tenants = Tenant::with('domains')->orderBy('id')->get();
        $flagDefinitions = FeatureFlag::cases();

        return view('landlord.feature-flags.index', compact('tenants', 'flagDefinitions'));
    }

    public function index(Tenant $tenant): View
    {
        $flags = $this->featureFlagService->getAll($tenant);
        $flagDefinitions = FeatureFlag::cases();

        return view('landlord.tenants.feature-flags.index', compact('tenant', 'flags', 'flagDefinitions'));
    }

    public function update(TenantFeatureFlagUpdateRequest $request, Tenant $tenant): RedirectResponse
    {
        $rawFlags = $request->validated('flags', []);
        /** @var array<int, string> $enabledFlags */
        $enabledFlags = is_array($rawFlags) ? $rawFlags : [];

        foreach (FeatureFlag::cases() as $flag) {
            if (in_array($flag->value, $enabledFlags, true)) {
                $this->featureFlagService->activate($flag, $tenant);
            } else {
                $this->featureFlagService->deactivate($flag, $tenant);
            }
        }

        return back()->with('success', __('flash.feature_flags.updated'));
    }

    public function bulkUpdate(BulkFeatureFlagUpdateRequest $request): RedirectResponse
    {
        $rawFlag = $request->validated('flag');
        $flag = FeatureFlag::from(is_string($rawFlag) ? $rawFlag : '');

        if ($request->validated('action') === 'enable') {
            $this->featureFlagService->activateForAll($flag);
        } else {
            $this->featureFlagService->deactivateForAll($flag);
        }

        return back()->with('success', __('flash.feature_flags.bulk_updated'));
    }
}
