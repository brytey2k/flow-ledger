<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Role;
use App\Models\Tenant\Branch;
use App\Models\Tenant\PaymentRequest;
use App\Models\Tenant\RetirementRequest;
use App\Models\Tenant\Staff;
use App\Models\Tenant\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Relations\Relation;
use Spatie\Activitylog\Models\Activity;

class ActivityLogRepository
{
    /** @var array<string, class-string> */
    public array $subjectTypes = [
        'user' => User::class,
        'staff' => Staff::class,
        'payment_request' => PaymentRequest::class,
        'retirement_request' => RetirementRequest::class,
        'role' => Role::class,
        'branch' => Branch::class,
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
            // Rows written before the morph map was introduced store the FQCN; newer rows store the alias.
            $query->whereIn('subject_type', [$subjectType, $this->subjectTypes[$subjectType]]);
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

    public function find(int $id): Activity
    {
        return Activity::with(['causer', 'subject'])->findOrFail($id);
    }

    /**
     * Normalizes a stored subject_type/causer_type value — either an Eloquent morph map
     * alias ('user'), one of this repository's own filter aliases not registered with
     * Eloquent ('payment_request', see MorphMapServiceProvider for why), or a legacy
     * FQCN (App\Models\Tenant\PaymentRequest) — to a human label.
     *
     * @param string|null $rawType
     */
    public function subjectLabel(string|null $rawType): string
    {
        if ($rawType === null || $rawType === '') {
            return '';
        }

        $knownTypes = $this->subjectTypes + Relation::morphMap();

        if (isset($knownTypes[$rawType])) {
            return $this->labelForAlias($rawType);
        }

        $alias = array_search($rawType, $knownTypes, true);

        if ($alias !== false) {
            return $this->labelForAlias($alias);
        }

        return class_basename($rawType);
    }

    private function labelForAlias(string $alias): string
    {
        return match ($alias) {
            'payment_request' => 'Payment Request',
            'retirement_request' => 'Retirement Request',
            default => ucwords(str_replace('_', ' ', $alias)),
        };
    }
}
