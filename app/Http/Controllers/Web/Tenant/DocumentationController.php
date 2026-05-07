<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Tenant;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class DocumentationController extends Controller
{
    public function index(): View
    {
        return view('tenant.documentation.index');
    }
}
