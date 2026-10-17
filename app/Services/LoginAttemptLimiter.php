<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\RateLimiter;

/**
 * Locks an account after repeated failed sign-ins, whatever IP they come from. The per-IP route
 * throttle stops a single client; this stops a distributed guessing attack on one account.
 */
class LoginAttemptLimiter
{
    public const MAX_ATTEMPTS = 10;

    public const LOCKOUT_SECONDS = 900;

    public function isLocked(string $scope, string $email): bool
    {
        return RateLimiter::tooManyAttempts($this->key($scope, $email), self::MAX_ATTEMPTS);
    }

    public function minutesUntilUnlocked(string $scope, string $email): int
    {
        return max(1, (int) ceil(RateLimiter::availableIn($this->key($scope, $email)) / 60));
    }

    /**
     * Records a failed attempt and reports whether this one tipped the account into lockout.
     *
     * @param string $scope
     * @param string $email
     */
    public function recordFailure(string $scope, string $email): bool
    {
        $attempts = RateLimiter::hit($this->key($scope, $email), self::LOCKOUT_SECONDS);

        return $attempts === self::MAX_ATTEMPTS;
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
