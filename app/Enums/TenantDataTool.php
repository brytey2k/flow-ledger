<?php

declare(strict_types=1);

namespace App\Enums;

enum TenantDataTool: string
{
    case ClearPaymentRequests = 'clear-payment-requests';
    case ClearRetirements = 'clear-retirements';
    case ClearCashbook = 'clear-cashbook';
    case ResetInFlightWorkflows = 'reset-in-flight-workflows';
    case ClearActivityLog = 'clear-activity-log';
    case RecalculateCashbookBalances = 'recalculate-cashbook-balances';
    case ExportData = 'export-tenant-data';

    public function label(): string
    {
        return match ($this) {
            self::ClearPaymentRequests => 'Clear payment requests',
            self::ClearRetirements => 'Clear retirements',
            self::ClearCashbook => 'Clear cashbook entries & cash counts',
            self::ResetInFlightWorkflows => 'Reset in-flight workflows',
            self::ClearActivityLog => 'Clear activity log',
            self::RecalculateCashbookBalances => 'Recalculate cashbook balances',
            self::ExportData => 'Export tenant data (CSV)',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::ClearPaymentRequests => 'Permanently deletes payment requests with their items, retirements, workflow history, comments, attachments (rows and files), activity and linked cashbook entries (balances are reversed).',
            self::ClearRetirements => 'Permanently deletes retirements with their items, workflow history, comments, attachments, reminder logs and settlement cashbook entries. Retired payment requests go back to Disbursed.',
            self::ClearCashbook => 'Permanently deletes cashbook entries and cash counts, then lowers or raises balances by the amount removed.',
            self::ResetInFlightWorkflows => 'Deletes workflow instances that are still in progress and returns their requests to Draft so they can be resubmitted. Completed workflows are untouched.',
            self::ClearActivityLog => 'Permanently deletes activity log rows.',
            self::RecalculateCashbookBalances => 'Sets each cashbook balance to the sum of its entries. Nothing is deleted.',
            self::ExportData => 'Builds a ZIP of CSV files covering requests, retirements, workflow history, cashbook, cash counts, attachment metadata (not the files themselves) and the activity log. Run this before any clear.',
        };
    }

    public function isDestructive(): bool
    {
        return ! in_array($this, [self::RecalculateCashbookBalances, self::ExportData], true);
    }

    public function supportsStatusFilter(): bool
    {
        return in_array($this, [self::ClearPaymentRequests, self::ClearRetirements], true);
    }

    public function supportsKeepDrafts(): bool
    {
        return $this->supportsStatusFilter();
    }

    public function supportsDateRange(): bool
    {
        return $this->isDestructive();
    }

    /** @return list<string> */
    public function statusOptions(): array
    {
        return match ($this) {
            self::ClearPaymentRequests => ['draft', 'in_workflow', 'approved', 'disbursed', 'retired', 'cancelled', 'denied', 'sent_back'],
            self::ClearRetirements => ['draft', 'in_workflow', 'approved', 'settled', 'cancelled', 'sent_back'],
            default => [],
        };
    }
}
