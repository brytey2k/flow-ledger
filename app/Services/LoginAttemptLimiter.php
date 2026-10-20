<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\RateLimiter;

/**
 * Locks an account after repeated failed sign-ins, whatever IP they come from. The per-IP route
 * throttle stops a single client; this stops a distributed guessing attack on one account.
 *
 * Every sign-in attempt reserves a slot with reserve() before credentials are checked, so
 * concurrent requests cannot slip past the limit between a check and a count. A successful
 * sign-in or password reset releases the slots with clear().
 */
class LoginAttemptLimiter
{
    public const MAX_ATTEMPTS = 10;

    public const LOCKOUT_SECONDS = 900;

    public function tenantScope(): string
    {
        $tenantId = tenant('id');

        return 'tenant:' . (is_string($tenantId) ? $tenantId : '');
    }

    /**
     * Reserves an attempt slot and returns how many attempts the account has used in the window.
     *
     * @param string $scope
     * @param string $email
     */
    public function reserve(string $scope, string $email): int
    {
        return RateLimiter::hit($this->key($scope, $email), self::LOCKOUT_SECONDS);
    }

    public function isOverLimit(int $attempts): bool
    {
        return $attempts > self::MAX_ATTEMPTS;
    }

    /**
     * Whether the attempt just reserved is the one that uses up the allowance, so that failing
     * it locks the account.
     *
     * @param int $attempts
     */
    public function isFinalAttempt(int $attempts): bool
    {
        return $attempts === self::MAX_ATTEMPTS;
    }

    public function minutesUntilUnlocked(string $scope, string $email): int
    {
        return max(1, (int) ceil(RateLimiter::availableIn($this->key($scope, $email)) / 60));
    }

    public function clear(string $scope, string $email): void
    {
        RateLimiter::clear($this->key($scope, $email));
    }

    private function key(string $scope, string $email): string
    {
        return 'login-lockout:' . $scope . ':' . sha1(mb_strtolower(trim($email)));
    }
}
