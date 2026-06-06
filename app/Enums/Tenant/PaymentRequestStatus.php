<?php

declare(strict_types=1);

namespace App\Enums\Tenant;

enum PaymentRequestStatus: string
{
    case Draft = 'draft';
    case InWorkflow = 'in_workflow';
    case Approved = 'approved';
    case Disbursed = 'disbursed';
    case Cancelled = 'cancelled';
    case Denied = 'denied';
    case SentBack = 'sent_back';

    public function isCancelable(): bool
    {
        return in_array($this, [
            self::Draft,
            self::InWorkflow,
            self::Approved,
            self::SentBack,
        ], true);
    }

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::InWorkflow => 'In Workflow',
            self::Approved => 'Approved',
            self::Disbursed => 'Disbursed',
            self::Cancelled => 'Cancelled',
            self::Denied => 'Denied',
            self::SentBack => 'Sent Back',
        };
    }
}
