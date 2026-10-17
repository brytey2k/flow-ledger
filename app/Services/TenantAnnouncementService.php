<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Tenant;

class TenantAnnouncementService
{
    /** @return array{announcement_message: string, announcement_level: string, maintenance_mode: bool, maintenance_message: string} */
    public function get(Tenant $tenant): array
    {
        return [
            'announcement_message' => $this->string($tenant, 'announcement_message'),
            'announcement_level' => $this->string($tenant, 'announcement_level') ?: 'info',
            'maintenance_mode' => (bool) $tenant->getAttribute('maintenance_mode'),
            'maintenance_message' => $this->string($tenant, 'maintenance_message'),
        ];
    }

    /** @param array{announcement_message?: string|null, announcement_level?: string|null, maintenance_mode?: bool|null, maintenance_message?: string|null} $data */
    public function update(Tenant $tenant, array $data): void
    {
        $tenant->setAttribute('announcement_message', $data['announcement_message'] ?? null);
        $tenant->setAttribute('announcement_level', $data['announcement_level'] ?? 'info');
        $tenant->setAttribute('maintenance_mode', (bool) ($data['maintenance_mode'] ?? false));
        $tenant->setAttribute('maintenance_message', $data['maintenance_message'] ?? null);
        $tenant->save();
    }

    private function string(Tenant $tenant, string $key): string
    {
        $value = $tenant->getAttribute($key);

        return is_string($value) ? $value : '';
    }
}
