<?php

declare(strict_types=1);

namespace App\Enums\Tenant;

enum PaymentRequestType: string
{
    case Advance = 'advance';
    case Expense = 'expense';
    case Retirement = 'retirement';
}
