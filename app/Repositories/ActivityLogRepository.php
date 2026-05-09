<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Tenant\PaymentRequest;
use App\Models\Tenant\RetirementRequest;
use App\Models\Tenant\Staff;
use App\Models\Tenant\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Spatie\Activitylog\Models\Activity;

class ActivityLogRepository
{
    /** @var array<string, class-string> */
    public array $subjectTypes = [
        'user' => User::class,
        'staff' => Staff::class,
        'payment_request' => PaymentRequest::class,
        'retirement_request' => RetirementRequest::class,
    ];

    /**
     * @param string|null|null $subjectType
     * @param string|null|null $event
     * @param string|null|null $causerSearch
     * @param int $perPage
     *
     * @return LengthAwarePaginator<int, Activity>
     */
    public function paginated(
        string|null $subjectType = null,
        string|null $event = null,
        string|null $causerSearch = null,
        int $perPage = 50,
    ): LengthAwarePaginator {
        $query = Activity::with(['causer', 'subject'])->orderByDesc('created_at');

        if ($subjectType !== null && isset($this->subjectTypes[$subjectType])) {
            $query->where('subject_type', $this->subjectTypes[$subjectType]);
        }

        if ($event !== null && $event !== '') {
            $query->where('event', $event);
        }

        if ($causerSearch !== null && $causerSearch !== '') {
            $query->whereHasMorph('causer', [User::class], function ($q) use ($causerSearch): void {
                $q->where('first_name', 'ilike', "%{$causerSearch}%")
                    ->orWhere('last_name', 'ilike', "%{$causerSearch}%")
                    ->orWhere('email', 'ilike', "%{$causerSearch}%");
            });
        }

        return $query->paginate($perPage)->withQueryString();
    }
}
