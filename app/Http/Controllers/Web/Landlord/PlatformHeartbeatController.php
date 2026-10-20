<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Landlord;

use App\Http\Controllers\Controller;
use App\Services\PlatformHeartbeatService;
use Illuminate\Http\JsonResponse;

class PlatformHeartbeatController extends Controller
{
    /**
     * For an external uptime monitor. Answers 200 while the scheduler and the queue workers are running and
     * 503 when either has stopped, and shows nothing beyond that.
     *
     * @param PlatformHeartbeatService $heartbeat
     */
    public function __invoke(PlatformHeartbeatService $heartbeat): JsonResponse
    {
        $status = $heartbeat->status();

        return response()->json([
            'scheduler' => $status['scheduler']['ok'] ? 'ok' : 'stale',
            'queue' => $status['queue']['ok'] ? 'ok' : 'stale',
        ], $status['ok'] ? 200 : 503);
    }
}
