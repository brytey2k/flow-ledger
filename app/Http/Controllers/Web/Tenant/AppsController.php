<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Tenant;

use App\Http\Controllers\Controller;
use App\Services\IdpAppListService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class AppsController extends Controller
{
    public function __construct(private readonly IdpAppListService $appList) {}

    /**
     * The other apps published by the IdP, each with a homepage link and an SSO sign-in link.
     */
    public function index(): JsonResponse
    {
        try {
            $apps = $this->appList->getOtherApps();
        } catch (RuntimeException $exception) {
            Log::warning('Failed to fetch the app list from the IdP', ['error' => $exception->getMessage()]);

            return response()->json(['message' => __('navigation.apps_unavailable')], 502);
        }

        return response()->json([
            'data' => array_map(static fn(array $app): array => [
                'name' => $app['name'],
                'homepage_url' => $app['homepage_url'],
                'sign_in_url' => $app['sign_in_url'],
            ], $apps),
        ]);
    }
}
