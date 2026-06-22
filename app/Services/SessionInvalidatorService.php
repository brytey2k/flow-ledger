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

    public function invalidate(int $userId): void
    {
        Cache::put("force_logout:{$userId}", true, now()->addHours(24));
        DB::table('sessions')->where('user_id', $userId)->delete();
    }
}
