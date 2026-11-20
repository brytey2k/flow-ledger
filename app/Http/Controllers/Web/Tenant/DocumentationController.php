<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Tenant;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Config;

class DocumentationController extends Controller
{
    public function index(): RedirectResponse
    {
        return redirect()->away(Config::string('app.docs_url'));
    }
}
