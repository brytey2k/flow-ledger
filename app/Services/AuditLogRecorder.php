<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AuditLogStatus;
use App\Models\Landlord\AuditLog;
use App\Models\Landlord\User as LandlordUser;
use Closure;
use Illuminate\Support\Facades\Auth;
use Throwable;

class AuditLogRecorder
{
    /**
     * Runs a landlord action and records who ran it, with what parameters and with what outcome.
     *
     * @param string $tenantId
     * @param string $action
     * @param array<string, mixed> $parameters
     * @param Closure(): array<string, int> $callback
     *
     * @return array<string, int>
     */
    public function record(string $tenantId, string $action, array $parameters, Closure $callback): array
    {
        $user = Auth::guard('landlord')->user();

        $log = AuditLog::create([
            'landlord_user_id' => $user?->getAuthIdentifier(),
            'landlord_user_email' => $user instanceof LandlordUser ? $user->email : null,
            'tenant_id' => $tenantId,
            'action' => $action,
            'status' => AuditLogStatus::Running,
            'filters' => $parameters,
            'started_at' => now(),
        ]);

        try {
            $result = $callback();
        } catch (Throwable $throwable) {
            $log->update(['status' => AuditLogStatus::Failed, 'error' => mb_substr($throwable->getMessage(), 0, 1000), 'finished_at' => now()]);

            throw $throwable;
        }

        $log->update(['status' => AuditLogStatus::Completed, 'result' => $result, 'finished_at' => now()]);

        return $result;
    }
}
