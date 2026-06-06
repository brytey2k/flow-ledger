<?php

declare(strict_types=1);

namespace App\Rules\Tenant;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\DB;
use Illuminate\Translation\PotentiallyTranslatedString;

class UniqueReceiptNumber implements ValidationRule
{
    public function __construct(
        private readonly int|null $excludeRetirementRequestId = null,
        private readonly int|null $excludePaymentRequestId = null,
    ) {}

    /** @param Closure(string, ?string=): PotentiallyTranslatedString $fail */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $retirementQuery = DB::table('retirement_request_items')
            ->join('retirement_requests', 'retirement_requests.id', '=', 'retirement_request_items.retirement_request_id')
            ->where('retirement_request_items.receipt_number', $value)
            ->where('retirement_requests.status', '!=', 'cancelled');

        if ($this->excludeRetirementRequestId !== null) {
            $retirementQuery->where('retirement_request_items.retirement_request_id', '!=', $this->excludeRetirementRequestId);
        }

        $paymentQuery = DB::table('payment_request_items')
            ->join('payment_requests', 'payment_requests.id', '=', 'payment_request_items.payment_request_id')
            ->where('payment_request_items.receipt_number', $value)
            ->whereNull('payment_requests.deleted_at');

        if ($this->excludePaymentRequestId !== null) {
            $paymentQuery->where('payment_request_items.payment_request_id', '!=', $this->excludePaymentRequestId);
        }

        if ($retirementQuery->exists() || $paymentQuery->exists()) {
            $fail(__('validation.receipt_number_taken'));
        }
    }
}
