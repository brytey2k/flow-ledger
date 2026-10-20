<?php

declare(strict_types=1);

namespace App\Enums;

enum TenantFixUp: string
{
    case ForceCancelRequest = 'force-cancel-request';
    case ReopenRetirement = 'reopen-retirement';
    case ReverseDisbursement = 'reverse-disbursement';
    case ResendRetirementReminders = 'resend-retirement-reminders';

    public function label(): string
    {
        return match ($this) {
            self::ForceCancelRequest => 'Force-cancel a stuck request',
            self::ReopenRetirement => 'Re-open a settled retirement',
            self::ReverseDisbursement => 'Reverse a disbursement',
            self::ResendRetirementReminders => 'Re-send retirement reminders',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::ForceCancelRequest => 'Cancels a payment or retirement request in any cancelable state, including requests blocked by an open document window. Open document requests are cancelled first.',
            self::ReopenRetirement => 'Moves a settled retirement back to Approved, removes its settlement cashbook entry (balance reversed) and returns the payment request to Disbursed so it can be settled again.',
            self::ReverseDisbursement => 'Moves a disbursed payment request back to Approved and removes its cashbook entry (balance restored). Refused if a retirement exists that is not cancelled.',
            self::ResendRetirementReminders => 'Clears today\'s reminder log (for one payment request, or all) and runs the reminder sweep again.',
        };
    }
}
