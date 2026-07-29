<?php

declare(strict_types=1);

namespace App\Enums\Tenant;

enum WorkflowTemplateStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
}
