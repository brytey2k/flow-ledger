<?php

declare(strict_types=1);

namespace App\Models\Landlord;

use App\Enums\BackupStatus;
use Database\Factories\Landlord\BackupRunFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

/**
 * @property int $id
 * @property string $database_name
 * @property string|null $tenant_id
 * @property BackupStatus $status
 * @property string|null $path
 * @property int|null $size_bytes
 * @property string|null $checksum
 * @property string|null $error
 * @property bool $is_monthly
 * @property \Carbon\Carbon|null $started_at
 * @property \Carbon\Carbon|null $finished_at
 * @property \Carbon\Carbon|null $verified_at
 * @property string|null $verify_status
 * @property string|null $verify_error
 * @property \Carbon\Carbon|null $pruned_at
 */
class BackupRun extends Model
{
    /** @use HasFactory<BackupRunFactory> */
    use HasFactory;
    use CentralConnection;

    protected $table = 'backup_runs';

    protected $fillable = [
        'database_name',
        'tenant_id',
        'status',
        'path',
        'size_bytes',
        'checksum',
        'error',
        'is_monthly',
        'started_at',
        'finished_at',
        'verified_at',
        'verify_status',
        'verify_error',
        'pruned_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => BackupStatus::class,
            'is_monthly' => 'boolean',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'verified_at' => 'datetime',
            'pruned_at' => 'datetime',
        ];
    }

    /**
     * Completed backups whose file has not been pruned.
     *
     * @param Builder<BackupRun> $query
     *
     * @return Builder<BackupRun>
     */
    public function scopeUsable(Builder $query): Builder
    {
        return $query->where('status', BackupStatus::Completed->value)->whereNull('pruned_at');
    }
}
