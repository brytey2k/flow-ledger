<?php

declare(strict_types=1);

namespace App\Services;

use App\Interfaces\SessionInvalidatorInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SessionInvalidatorService implements SessionInvalidatorInterface
{
    public function track(int $userId, string $sessionId): void
    {
        Cache::forget("force_logout:{$userId}");
    }

    /** Bearer tokens issued at or before this time are refused; it is not cleared when the user signs in again. */
    public static function apiRevokedBeforeKey(int $userId): string
    {
        return "api_revoked_before:{$userId}";
    }

    public function invalidate(int $userId): void
    {
        Cache::put("force_logout:{$userId}", true, now()->addHours(24));
        Cache::put(self::apiRevokedBeforeKey($userId), now()->getTimestamp(), now()->addDays(7));
        DB::table('sessions')->where('user_id', $userId)->delete();
    }

    public function revokeOtherSessions(int $userId, string|null $exceptSessionId = null): void
    {
        DB::table('sessions')
            ->where('user_id', $userId)
            ->when($exceptSessionId !== null, fn($query) => $query->where('id', '!=', $exceptSessionId))
            ->delete();
    }
}
