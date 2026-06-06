<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\SettingsUpdateRequest;
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
    ) {}

    public function index(): View
    {
        $logoUrl = $this->settingsService->getLogoUrl();
        $costCodes = $this->costCodeRepository->allWithDepartment();
        $defaultAdvanceCostCodeId = $this->settingsService->getDefaultAdvanceCostCodeId();
        $requireExpenseSourceDocuments = $this->settingsService->isExpenseSourceDocumentRequired();
        $requireRetirementSourceDocuments = $this->settingsService->isRetirementSourceDocumentRequired();
        $retirementReminderSettings = $this->settingsService->getRetirementReminderSettings();
        $roles = $this->roleRepository->allOrderedByName();

        return view('tenant.settings.index', compact('logoUrl', 'costCodes', 'defaultAdvanceCostCodeId', 'requireExpenseSourceDocuments', 'requireRetirementSourceDocuments', 'retirementReminderSettings', 'roles'));
    }

    public function update(SettingsUpdateRequest $request): RedirectResponse
    {
        if ($request->boolean('remove_logo')) {
            $this->settingsService->removeLogo();
        } elseif ($request->hasFile('logo')) {
            $this->settingsService->storeLogo($request->file('logo'));
        }

        if ($request->has('default_advance_cost_code_id')) {
            $costCodeId = $request->input('default_advance_cost_code_id');
            $this->settingsService->setDefaultAdvanceCostCode($costCodeId ? (int) $costCodeId : null);
        }

        $this->settingsService->setRequireExpenseSourceDocuments($request->boolean('require_expense_source_documents'));
        $this->settingsService->setRequireRetirementSourceDocuments($request->boolean('require_retirement_source_documents'));

        $this->settingsService->setRetirementReminderSettings([
            'grace_period_days' => (int) $request->input('retirement_reminder_grace_period_days', 7),
            'frequency_days' => (int) $request->input('retirement_reminder_frequency_days', 7),
            'notify_submitter' => $request->boolean('retirement_reminder_notify_submitter'),
            'notify_approvers' => $request->boolean('retirement_reminder_notify_approvers'),
            'notify_role_ids' => array_map('intval', $request->input('retirement_reminder_notify_role_ids', [])),
        ]);

        return redirect()->route('settings.index')->with('success', __('flash.settings.updated'));
    }
}
