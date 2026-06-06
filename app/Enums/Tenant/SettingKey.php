<?php

declare(strict_types=1);

namespace App\Enums\Tenant;

enum SettingKey: string
{
    case Logo = 'logo';
    case DefaultAdvanceCostCode = 'default_advance_cost_code';
    case RequireExpenseSourceDocuments = 'require_expense_source_documents';
    case RequireRetirementSourceDocuments = 'require_retirement_source_documents';
    case RetirementReminders = 'retirement_reminders';
}
