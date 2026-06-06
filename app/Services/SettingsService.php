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

    public function getLogoUrl(): string|null
    {
        $setting = $this->repository->get(SettingKey::Logo);
        $path = $setting['path'] ?? null;

        if (! $path) {
            return null;
        }

        return Storage::disk('public')->url($path);
    }

    public function storeLogo(UploadedFile $file): void
    {
        $existing = $this->repository->get(SettingKey::Logo);

        if (! empty($existing['path'])) {
            Storage::disk('public')->delete($existing['path']);
        }

        $path = $file->store('branding', 'public');

        $this->repository->set(SettingKey::Logo, ['path' => $path]);
    }

    public function removeLogo(): void
    {
        $existing = $this->repository->get(SettingKey::Logo);

        if (! empty($existing['path'])) {
            Storage::disk('public')->delete($existing['path']);
        }

        $this->repository->set(SettingKey::Logo, ['path' => null]);
    }

    public function getDefaultAdvanceCostCodeId(): int|null
    {
        $setting = $this->repository->get(SettingKey::DefaultAdvanceCostCode);

        return isset($setting['cost_code_id']) ? (int) $setting['cost_code_id'] : null;
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
}
