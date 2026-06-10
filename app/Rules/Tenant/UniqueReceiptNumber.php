<?php

declare(strict_types=1);

namespace App\Rules\Tenant;

use App\Models\Tenant\PaymentRequestItem;
use App\Models\Tenant\RetirementRequestItem;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
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
        $retirementExists = RetirementRequestItem::where('receipt_number', $value)
            ->whereHas('retirementRequest', fn($q) => $q->where('status', '!=', 'cancelled'))
            ->when($this->excludeRetirementRequestId !== null, fn($q) => $q->where('retirement_request_id', '!=', $this->excludeRetirementRequestId))
            ->exists();

        $paymentExists = PaymentRequestItem::where('receipt_number', $value)
            ->whereHas('paymentRequest', fn($q) => $q->whereNotIn('status', ['cancelled', 'denied']))
            ->when($this->excludePaymentRequestId !== null, fn($q) => $q->where('payment_request_id', '!=', $this->excludePaymentRequestId))
            ->exists();

        if ($retirementExists || $paymentExists) {
            $fail(__('validation.receipt_number_taken'));
        }
    }
}
