<?php

declare(strict_types=1);

namespace App\Enums\Tenant;

enum WorkflowDocumentRequestStatus: string
{
    case AwaitingUploads = 'awaiting_uploads';
    case Submitted = 'submitted';
    case AwaitingReReview = 'awaiting_re_review';
    case Resolved = 'resolved';
    case Cancelled = 'cancelled';

    public function isUnresolved(): bool
    {
        return in_array($this, [self::AwaitingUploads, self::Submitted, self::AwaitingReReview], true);
    }
}
