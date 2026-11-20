<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\DemoDataService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Throwable;

/**
 * Creates (or with --fresh, recreates) the fictional "Savanna Relief Trust" demo tenant at
 * demo.<central domain> and fills it with demo data for user-guide screenshots.
 *
 * Every demo login uses the local test password "password"; the administrator is
 * admin@demo.test and the other people are <first>.<last>@demo.test.
 */
#[Signature('demo:seed
    {--fresh : Delete the demo tenant (database and attachments) and build it again}
    {--force : Run outside local, testing and staging environments}')]
#[Description('Create the fictional "Savanna Relief Trust" demo tenant (admin@demo.test / password; every demo user\'s password is "password") with requests in every state')]
class SeedDemoTenant extends Command
{
    public function handle(DemoDataService $demoData): int
    {
        // Demo seeding creates logins with a well-known password and --fresh drops a tenant
        // database, so it must never run against a real environment by accident.
        if (! app()->environment('local', 'testing', 'staging') && ! $this->option('force')) {
            $this->error('demo:seed only runs in local, testing or staging environments. Pass --force to override.');

            return self::FAILURE;
        }

        // Approvals and low-cash alerts send queued notification mail. Run it inline into the
        // in-memory mailer, so no demo mail reaches a queue worker or a real mail provider.
        config([
            'mail.default' => 'array',
            'queue.default' => 'sync',
        ]);

        try {
            $tenant = $demoData->provisionTenant((bool) $this->option('fresh'));
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        tenancy()->initialize($tenant);

        try {
            $result = $demoData->seed();
        } finally {
            tenancy()->end();
        }

        if (! $result['created']) {
            $this->warn('Demo requests already exist, so only the reference data was topped up. Pass --fresh to rebuild everything.');
        }

        $this->table(
            ['ID', 'Type', 'Status', 'Retirement', 'Requester', 'Branch', 'GHS', 'Description'],
            array_map(static fn(array $row): array => [
                $row['id'],
                $row['type'],
                $row['status'],
                $row['retirement_id'] !== null ? "#{$row['retirement_id']} {$row['retirement_status']}" : '',
                $row['requester'],
                $row['branch'],
                $row['amount'],
                $row['notes'],
            ], $result['requests']),
        );

        $domain = $tenant->domains()->first()?->domain;
        $this->info("Demo tenant ready: http://{$domain}/login");
        $this->line('Sign in as ' . DemoDataService::ADMIN_EMAIL . ' (or any <first>.<last>@demo.test user) with the password "' . DemoDataService::PASSWORD . '".');

        return self::SUCCESS;
    }
}
