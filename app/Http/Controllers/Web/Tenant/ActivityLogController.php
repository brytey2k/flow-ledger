<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Tenant;

use App\Http\Controllers\Controller;
use App\Repositories\ActivityLogRepository;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ActivityLogController extends Controller
{
    public function __construct(
        private readonly ActivityLogRepository $repository,
    ) {}

    public function index(Request $request): View
    {
        $subjectType = $request->filled('subject_type') ? $request->string('subject_type')->toString() : null;
        $event = $request->filled('event') ? $request->string('event')->toString() : null;
        $causer = $request->filled('causer') ? $request->string('causer')->toString() : null;

        $logs = $this->repository->paginated($subjectType, $event, $causer);

        $subjectLabels = array_flip(array_map(
            fn(string $class) => class_basename($class),
            $this->repository->subjectTypes,
        ));

        return view('tenant.activity-log.index', compact('logs', 'subjectLabels'));
    }
}
