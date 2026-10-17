<?php

declare(strict_types=1);

namespace Database\Factories\Landlord;

use App\Enums\BackupStatus;
use App\Models\Landlord\BackupRun;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BackupRun>
 */
class BackupRunFactory extends Factory
{
    protected $model = BackupRun::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $finished = now()->subHour();

        return [
            'database_name' => 'tenant_' . fake()->unique()->lexify('????????') . '_2026_01_01',
            'tenant_id' => null,
            'status' => BackupStatus::Completed,
            'path' => 'backups/' . fake()->uuid() . '.dump.enc',
            'size_bytes' => fake()->numberBetween(1000, 5000000),
            'checksum' => hash('sha256', fake()->uuid()),
            'is_monthly' => false,
            'started_at' => $finished->copy()->subMinute(),
            'finished_at' => $finished,
        ];
    }

    public function failed(string $error = 'pg_dump failed'): static
    {
        return $this->state(['status' => BackupStatus::Failed, 'path' => null, 'checksum' => null, 'size_bytes' => null, 'error' => $error]);
    }
}
