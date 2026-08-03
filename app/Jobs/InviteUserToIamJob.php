<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Tenant\User;
use App\Services\IamUserProvisioningService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class InviteUserToIamJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * Kept comfortably below the queue connection's retry_after (90s) so a genuinely
     * stuck job fails cleanly via $tries/backoff instead of silently becoming visible
     * again and being picked up by a second worker while the first is still running.
     */
    public int $timeout = 45;

    /**
     * Covers the full retry lifecycle (initial attempt + backoff delays + timeouts)
     * so a retried attempt can't run concurrently with another dispatch for the same user.
     */
    public int $uniqueFor = 600;

    public function __construct(
        public readonly int $userId,
        public readonly string|null $idpTenantId,
    ) {}

    public function uniqueId(): string
    {
        return (string) $this->userId;
    }

    /** @return array<int, int> */
    public function backoff(): array
    {
        return [30, 60, 120];
    }

    public function handle(IamUserProvisioningService $iamUserProvisioningService): void
    {
        $user = User::find($this->userId);

        if ($user === null) {
            Log::warning('InviteUserToIamJob: user no longer exists.', ['user_id' => $this->userId]);

            return;
        }

        if ($this->idpTenantId === null || $this->idpTenantId === '') {
            Log::warning('InviteUserToIamJob: tenant has no idp_tenant_id, cannot invite.', ['user_id' => $this->userId]);

            return;
        }

        // The service throws on failure — let it bubble so the job retries.
        $result = $iamUserProvisioningService->invite($user, $this->idpTenantId);

        $user->update(['oidc_sub' => $result['iam_user_id']]);
    }
}
