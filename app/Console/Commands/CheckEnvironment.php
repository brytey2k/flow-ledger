<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Support\Deployment\ConfigurationAudit;
use App\Support\Deployment\ConfigurationIssue;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:check-environment {--production : Hold the configuration to production standards: every issue but a warning fails}')]
#[Description('Check the running configuration for unsafe or broken settings before a deploy goes live')]
class CheckEnvironment extends Command
{
    public function handle(ConfigurationAudit $audit): int
    {
        $issues = $audit->issues(production: (bool) $this->option('production'));

        if ($issues === []) {
            $this->components->info('The configuration passed every check.');

            return self::SUCCESS;
        }

        $blocking = array_values(array_filter($issues, fn(ConfigurationIssue $issue): bool => $issue->blocking));

        foreach ($issues as $issue) {
            $issue->blocking
                ? $this->components->error("{$issue->setting}: {$issue->message}")
                : $this->components->warn("{$issue->setting}: {$issue->message}");
        }

        if ($blocking !== []) {
            $this->components->error(count($blocking) . ' blocking issue(s). Fix them in .env (see .env.production.example) and re-run.');

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
