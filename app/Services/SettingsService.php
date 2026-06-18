<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\Tenant\SettingKey;
use App\Models\Tenant\CostCode;
use App\Repositories\SettingsRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class SettingsService
{
    public function __construct(
        private readonly SettingsRepository $repository,
    ) {}

    public function getLightLogoUrl(): string|null
    {
        return $this->getLogoUrlFor(SettingKey::LogoLight);
    }

    public function storeLightLogo(UploadedFile $file): void
    {
        $this->storeLogoFor(SettingKey::LogoLight, $file);
    }

    public function removeLightLogo(): void
    {
        $this->removeLogoFor(SettingKey::LogoLight);
    }

    public function getDarkLogoUrl(): string|null
    {
        return $this->getLogoUrlFor(SettingKey::LogoDark);
    }

    public function storeDarkLogo(UploadedFile $file): void
    {
        $this->storeLogoFor(SettingKey::LogoDark, $file);
    }

    public function removeDarkLogo(): void
    {
        $this->removeLogoFor(SettingKey::LogoDark);
    }

    public function getSmallLogoUrl(): string|null
    {
        return $this->getLogoUrlFor(SettingKey::LogoSmall);
    }

    public function storeSmallLogo(UploadedFile $file): void
    {
        $this->storeLogoFor(SettingKey::LogoSmall, $file);
    }

    public function removeSmallLogo(): void
    {
        $this->removeLogoFor(SettingKey::LogoSmall);
    }

    private function getLogoUrlFor(SettingKey $key): string|null
    {
        $setting = $this->repository->get($key);
        $path = $setting['path'] ?? null;

        if (! $path) {
            return null;
        }

        assert(is_string($path));

        return route('stancl.tenancy.asset', $path);
    }

    private function storeLogoFor(SettingKey $key, UploadedFile $file): void
    {
        $existing = $this->repository->get($key);

        if (! empty($existing['path'])) {
            /** @var string $existingPath */
            $existingPath = $existing['path'];
            Storage::disk('public')->delete($existingPath);
        }

        $path = $file->store('branding', 'public');

        $this->repository->set($key, ['path' => $path]);
    }

    private function removeLogoFor(SettingKey $key): void
    {
        $existing = $this->repository->get($key);

        if (! empty($existing['path'])) {
            /** @var string $existingPath */
            $existingPath = $existing['path'];
            Storage::disk('public')->delete($existingPath);
        }

        $this->repository->set($key, ['path' => null]);
    }

    public function getDefaultAdvanceCostCodeId(): int|null
    {
        $setting = $this->repository->get(SettingKey::DefaultAdvanceCostCode);

        if (! isset($setting['cost_code_id'])) {
            return null;
        }

        /** @var int $costCodeId */
        $costCodeId = $setting['cost_code_id'];

        return $costCodeId;
    }

    public function getDefaultAdvanceCostCode(): CostCode|null
    {
        $id = $this->getDefaultAdvanceCostCodeId();

        return $id ? CostCode::query()->find($id) : null;
    }

    public function setDefaultAdvanceCostCode(int|null $costCodeId): void
    {
        $this->repository->set(SettingKey::DefaultAdvanceCostCode, ['cost_code_id' => $costCodeId]);
    }

    public function isExpenseSourceDocumentRequired(): bool
    {
        $setting = $this->repository->get(SettingKey::RequireExpenseSourceDocuments);

        return (bool) ($setting['required'] ?? false);
    }

    public function setRequireExpenseSourceDocuments(bool $required): void
    {
        $this->repository->set(SettingKey::RequireExpenseSourceDocuments, ['required' => $required]);
    }

    public function isRetirementSourceDocumentRequired(): bool
    {
        $setting = $this->repository->get(SettingKey::RequireRetirementSourceDocuments);

        return (bool) ($setting['required'] ?? false);
    }

    public function setRequireRetirementSourceDocuments(bool $required): void
    {
        $this->repository->set(SettingKey::RequireRetirementSourceDocuments, ['required' => $required]);
    }

    /**
     * @return array{grace_period_days: int, frequency_days: int, notify_submitter: bool, notify_approvers: bool, notify_role_ids: list<int>}
     */
    public function getRetirementReminderSettings(): array
    {
        $setting = $this->repository->get(SettingKey::RetirementReminders) ?? [];

        return [
            'grace_period_days' => isset($setting['grace_period_days']) && is_scalar($setting['grace_period_days']) ? (int) $setting['grace_period_days'] : 7,
            'frequency_days' => isset($setting['frequency_days']) && is_scalar($setting['frequency_days']) ? (int) $setting['frequency_days'] : 7,
            'notify_submitter' => (bool) ($setting['notify_submitter'] ?? true),
            'notify_approvers' => (bool) ($setting['notify_approvers'] ?? true),
            'notify_role_ids' => array_values(array_map(static fn(mixed $v): int => is_scalar($v) ? (int) $v : 0, (array) ($setting['notify_role_ids'] ?? []))),
        ];
    }

    /**
     * @param array{grace_period_days: int, frequency_days: int, notify_submitter: bool, notify_approvers: bool, notify_role_ids: list<int>} $data
     */
    /**
     * @param array{grace_period_days: int, frequency_days: int, notify_submitter: bool, notify_approvers: bool, notify_role_ids: list<int>} $data
     */
    public function setRetirementReminderSettings(array $data): void
    {
        $this->repository->set(SettingKey::RetirementReminders, $data);
    }

    public function getSsoDefaultBranchId(): int|null
    {
        $setting = $this->repository->get(SettingKey::SsoDefaultBranch);

        if (! isset($setting['branch_id'])) {
            return null;
        }

        /** @var int $branchId */
        $branchId = $setting['branch_id'];

        return $branchId;
    }

    public function setSsoDefaultBranch(int|null $branchId): void
    {
        $this->repository->set(SettingKey::SsoDefaultBranch, ['branch_id' => $branchId]);
    }

    public function getSsoStaffRoleName(): string|null
    {
        $setting = $this->repository->get(SettingKey::SsoStaffRole);

        if (! isset($setting['role_name']) || ! is_string($setting['role_name'])) {
            return null;
        }

        return $setting['role_name'] ?: null;
    }

    public function setSsoStaffRoleName(string|null $roleName): void
    {
        $this->repository->set(SettingKey::SsoStaffRole, ['role_name' => $roleName]);
    }
}
