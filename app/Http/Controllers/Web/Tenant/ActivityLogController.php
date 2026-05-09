<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\PaymentRequest;
use App\Models\Tenant\RetirementRequest;
use App\Models\Tenant\Staff;
use App\Models\Tenant\User;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\Activitylog\Models\Activity;

class ActivityLogController extends Controller
{
    public function index(Request $request): View
    {
        $subjectTypes = [
            'user' => User::class,
            'staff' => Staff::class,
            'payment_request' => PaymentRequest::class,
            'retirement_request' => RetirementRequest::class,
        ];

        $query = Activity::with(['causer', 'subject'])
            ->orderByDesc('created_at');

        if ($request->filled('subject_type') && isset($subjectTypes[$request->string('subject_type')->toString()])) {
            $query->where('subject_type', $subjectTypes[$request->string('subject_type')->toString()]);
        }

        if ($request->filled('event')) {
            $query->where('event', $request->string('event')->toString());
        }

        if ($request->filled('causer')) {
            $causerSearch = $request->string('causer')->toString();
            $query->whereHasMorph('causer', [User::class], function ($q) use ($causerSearch): void {
                $q->where('first_name', 'ilike', "%{$causerSearch}%")
                    ->orWhere('last_name', 'ilike', "%{$causerSearch}%")
                    ->orWhere('email', 'ilike', "%{$causerSearch}%");
            });
        }

        $logs = $query->paginate(50)->withQueryString();

        $subjectLabels = array_flip(array_map(
            fn(string $class) => class_basename($class),
            $subjectTypes,
        ));

        return view('tenant.activity-log.index', compact('logs', 'subjectLabels'));
    }
}
