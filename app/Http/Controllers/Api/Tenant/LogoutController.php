<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Tenant;

use App\Interfaces\SessionInvalidatorInterface;
use Illuminate\Http\JsonResponse;

/**
 * App-scoped logout for the mobile client.
 *
 * API auth is stateless JWT, so this endpoint is advisory: it proactively
 * clears any server-side session state for the current user (mirroring what a
 * back-channel logout would do) when the user logs out of this app only. The
 * mobile client also revokes its tokens at the IdP; a global cross-RP logout
 * is handled separately by the IdP's end-session + back-channel broadcast.
 */
class LogoutController extends BaseApiController
{
    public function __construct(private readonly SessionInvalidatorInterface $sessionInvalidator) {}

    public function __invoke(): JsonResponse
    {
        $user = $this->apiUser();

        $this->sessionInvalidator->invalidate($user->id);

        return response()->json(['message' => 'Logged out.']);
    }
}
