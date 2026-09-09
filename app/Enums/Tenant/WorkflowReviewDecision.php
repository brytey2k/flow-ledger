<?php

declare(strict_types=1);

namespace App\Enums\Tenant;

enum WorkflowReviewDecision: string
{
    case Confirmed = 'confirmed';
    case Concern = 'concern';
}
