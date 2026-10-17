<?php

declare(strict_types=1);

namespace App\Interfaces;

interface SessionInvalidatorInterface
{
    /**
     * Track a new session for the user. Called after SSO login completes.
     * Clears any pending forced-logout flag for the user.
     *
     * @param int $userId
     * @param string $sessionId
     */
    public function track(int $userId, string $sessionId): void;

    /**
     * Invalidate all active sessions for the user.
     * Sets a cache key that the CheckForceLogout middleware watches,
     * and deletes session records from the sessions table.
     *
     * @param int $userId
     */
    public function invalidate(int $userId): void;

    /**
     * Delete the user's stored sessions and rotate their remember-me token, without forcing the
     * browser that is making the call to sign in again. Used after a password change or reset.
     *
     * @param int $userId
     * @param string|null $exceptSessionId session to keep, when the caller is signed in
     */
    public function revokeOtherSessions(int $userId, string|null $exceptSessionId = null): void;
}
