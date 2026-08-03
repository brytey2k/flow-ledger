<?php

declare(strict_types=1);

namespace App\Rules\Tenant;

use App\Models\Tenant\Branch;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class BranchCurrencyLocked implements ValidationRule
{
    public function __construct(
        private readonly Branch $branch,
    ) {}

    /** @param Closure(string, ?string=): PotentiallyTranslatedString $fail */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $submittedCurrencyId = is_numeric($value) ? (int) $value : null;

        if ($submittedCurrencyId === $this->branch->currency_id) {
            return;
        }

        if ($this->branch->cashbook()->exists()) {
            $fail(__('validation.branch_currency_locked'));
        }
    }
}
