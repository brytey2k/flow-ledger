<?php

declare(strict_types=1);

namespace App\Models\Landlord;

use App\Enums\AuditLogStatus;
use App\Enums\TenantDataTool;
use App\Exceptions\ImmutableRecordException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

/**
 * @property int $id
 * @property int|null $landlord_user_id
 * @property string|null $landlord_user_email
 * @property string $tenant_id
 * @property string $action
 * @property AuditLogStatus $status
 * @property array{statuses?: list<string>, from?: string|null, to?: string|null, keep_drafts?: bool}|null $filters
 * @property array<string, int>|null $result
 * @property string|null $error
 * @property string|null $artifact_path
 * @property \Carbon\Carbon|null $started_at
 * @property \Carbon\Carbon|null $finished_at
 * @property \Carbon\Carbon|null $created_at
 */
class AuditLog extends Model
{
    use CentralConnection;

    protected $table = 'landlord_audit_logs';

    protected $fillable = [
        'landlord_user_id',
        'landlord_user_email',
        'tenant_id',
        'action',
        'status',
        'filters',
        'result',
        'error',
        'artifact_path',
        'started_at',
        'finished_at',
    ];

    protected static function booted(): void
    {
        static::updating(static function (self $log): void {
            if ($log->getOriginal('status') === AuditLogStatus::Completed || $log->getOriginal('status') === AuditLogStatus::Failed) {
                throw new ImmutableRecordException('Finished audit log entries cannot be edited.');
            }
        });

        static::deleting(static function (): never {
            throw new ImmutableRecordException('Audit log entries cannot be deleted.');
        });
    }

    protected function casts(): array
    {
        return [
            'status' => AuditLogStatus::class,
            'filters' => 'array',
            'result' => 'array',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function tool(): TenantDataTool|null
    {
        return TenantDataTool::tryFrom($this->action);
    }

    public function label(): string
    {
        return $this->tool()?->label() ?? Str::headline($this->action);
    }
}
