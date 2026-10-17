<?php

declare(strict_types=1);

namespace App\Enums;

enum AuditLogStatus: string
{
    case Queued = 'queued';
    case Running = 'running';
    case Completed = 'completed';
    case Failed = 'failed';
}
