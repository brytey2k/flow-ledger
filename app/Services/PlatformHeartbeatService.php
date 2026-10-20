<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Cache;

/**
 * Tells whether the scheduler and the queue workers are running anywhere. Each leaves a timestamp in the
 * shared cache store, so it makes no difference which server runs them or how many there are.
 */
class PlatformHeartbeatService
{
    public const SCHEDULER_KEY = 'platform.heartbeat.scheduler';

    public const QUEUE_KEY = 'platform.heartbeat.queue';

    public const SCHEDULER_STALE_AFTER_SECONDS = 180;

    public const QUEUE_STALE_AFTER_SECONDS = 300;

    public function recordScheduler(): void
    {
        $this->store()->put(self::SCHEDULER_KEY, now()->getTimestamp(), now()->addDay());
    }

    public function recordQueue(): void
    {
        $this->store()->put(self::QUEUE_KEY, now()->getTimestamp(), now()->addDay());
    }

    /**
     * @return array{
     *     scheduler: array{ok: bool, last_seen_at: int|null},
     *     queue: array{ok: bool, last_seen_at: int|null},
     *     ok: bool
     * }
     */
    public function status(): array
    {
        $scheduler = $this->check(self::SCHEDULER_KEY, self::SCHEDULER_STALE_AFTER_SECONDS);
        $queue = $this->check(self::QUEUE_KEY, self::QUEUE_STALE_AFTER_SECONDS);

        return ['scheduler' => $scheduler, 'queue' => $queue, 'ok' => $scheduler['ok'] && $queue['ok']];
    }

    /** @return array{ok: bool, last_seen_at: int|null} */
    private function check(string $key, int $staleAfterSeconds): array
    {
        $seen = $this->store()->get($key);
        $lastSeen = is_numeric($seen) ? (int) $seen : null;

        return ['ok' => $lastSeen !== null && now()->getTimestamp() - $lastSeen <= $staleAfterSeconds, 'last_seen_at' => $lastSeen];
    }

    /** The explicit store name bypasses tenant cache tagging, so the value is the same wherever it is read. */
    private function store(): \Illuminate\Contracts\Cache\Repository
    {
        return Cache::store(config()->string('cache.default'));
    }
}
