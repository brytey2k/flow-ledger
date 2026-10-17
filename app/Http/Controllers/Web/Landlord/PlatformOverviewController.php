<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Landlord;

use App\Http\Controllers\Controller;
use App\Services\PlatformOverviewService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PlatformOverviewController extends Controller
{
    public function index(PlatformOverviewService $overview): View
    {
        return view('landlord.overview', $overview->overview());
    }

    public function refresh(PlatformOverviewService $overview): RedirectResponse
    {
        $queued = $overview->queueRefreshAll();

        return redirect()->route('landlord.overview.index')->with('success', "Refreshing {$queued} customer(s). Reload in a moment.");
    }
}
