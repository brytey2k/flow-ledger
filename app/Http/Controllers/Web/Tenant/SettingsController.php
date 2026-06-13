<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\SettingsUpdateRequest;
use App\Repositories\BranchRepository;
use App\Repositories\CostCodeRepository;
use App\Repositories\RoleRepository;
use App\Services\SettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function __construct(
        private readonly SettingsService $settingsService,
        private readonly CostCodeRepository $costCodeRepository,
        private readonly RoleRepository $roleRepository,
        private readonly BranchRepository $branchRepository,
    ) {}

    public function index(): View
    {
        $lightLogoUrl = $this->settingsService->getLightLogoUrl();
        $darkLogoUrl = $this->settingsService->getDarkLogoUrl();
        $smallLogoUrl = $this->settingsService->getSmallLogoUrl();
        $costCodes = $this->costCodeRepository->allWithDepartment();
        $defaultAdvanceCostCodeId = $this->settingsService->getDefaultAdvanceCostCodeId();
        $requireExpenseSourceDocuments = $this->settingsService->isExpenseSourceDocumentRequired();
        $requireRetirementSourceDocuments = $this->settingsService->isRetirementSourceDocumentRequired();
        $retirementReminderSettings = $this->settingsService->getRetirementReminderSettings();
        $roles = $this->roleRepository->allOrderedByName();
        $branches = $this->branchRepository->allOrderedByName();
        $ssoDefaultBranchId = $this->settingsService->getSsoDefaultBranchId();

        return view('tenant.settings.index', compact('lightLogoUrl', 'darkLogoUrl', 'smallLogoUrl', 'costCodes', 'defaultAdvanceCostCodeId', 'requireExpenseSourceDocuments', 'requireRetirementSourceDocuments', 'retirementReminderSettings', 'roles', 'branches', 'ssoDefaultBranchId'));
    }

    public function update(SettingsUpdateRequest $request): RedirectResponse
    {
        if ($request->boolean('remove_logo_light')) {
            $this->settingsService->removeLightLogo();
        } elseif ($request->hasFile('logo_light')) {
            $this->settingsService->storeLightLogo($request->file('logo_light'));
        }

        if ($request->boolean('remove_logo_dark')) {
            $this->settingsService->removeDarkLogo();
        } elseif ($request->hasFile('logo_dark')) {
            $this->settingsService->storeDarkLogo($request->file('logo_dark'));
        }

        if ($request->boolean('remove_logo_small')) {
            $this->settingsService->removeSmallLogo();
        } elseif ($request->hasFile('logo_small')) {
            $this->settingsService->storeSmallLogo($request->file('logo_small'));
        }

        if ($request->has('default_advance_cost_code_id')) {
            $rawCostCodeId = $request->integer('default_advance_cost_code_id');
            $this->settingsService->setDefaultAdvanceCostCode($rawCostCodeId ?: null);
        }

        $this->settingsService->setRequireExpenseSourceDocuments($request->boolean('require_expense_source_documents'));
        $this->settingsService->setRequireRetirementSourceDocuments($request->boolean('require_retirement_source_documents'));

        $this->settingsService->setRetirementReminderSettings([
            'grace_period_days' => $request->integer('retirement_reminder_grace_period_days', 7),
            'frequency_days' => $request->integer('retirement_reminder_frequency_days', 7),
            'notify_submitter' => $request->boolean('retirement_reminder_notify_submitter'),
            'notify_approvers' => $request->boolean('retirement_reminder_notify_approvers'),
            'notify_role_ids' => array_values(array_map(static fn(mixed $v): int => is_scalar($v) ? (int) $v : 0, (array) $request->input('retirement_reminder_notify_role_ids', []))),
        ]);

        if ($request->has('sso_default_branch_id')) {
            $rawBranchId = $request->integer('sso_default_branch_id');
            $this->settingsService->setSsoDefaultBranch($rawBranchId ?: null);
        }

        return redirect()->route('settings.index')->with('success', __('flash.settings.updated'));
    }
}
