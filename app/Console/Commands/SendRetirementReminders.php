<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\RetirementReminderService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Stancl\Tenancy\Concerns\HasATenantsOption;

#[Signature('retirement:send-reminders')]
#[Description('Send overdue retirement reminder notifications to configured recipients for each tenant')]
class SendRetirementReminders extends Command
{
    use HasATenantsOption;

    public function handle(RetirementReminderService $reminderService): int
    {
        $this->getTenants()->each(function (mixed $tenant) use ($reminderService): void {
            assert($tenant instanceof Tenant);
            tenancy()->initialize($tenant);

            $this->line("── Tenant: <fg=cyan>{$tenant->id}</>");
            $sent = $reminderService->sendReminders();
            $this->info("Sent {$sent} reminder(s).");

            tenancy()->end();
        });

        return self::SUCCESS;
    }
}
