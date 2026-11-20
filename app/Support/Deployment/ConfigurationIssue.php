<?php

declare(strict_types=1);

namespace App\Support\Deployment;

/** One problem with the running configuration, as found by ConfigurationAudit. */
final readonly class ConfigurationIssue
{
    /**
     * @param string $setting The environment variable(s) to change, e.g. "APP_DEBUG".
     * @param string $message
     * @param bool $blocking Whether a deploy must stop on it (a warning only gets reported).
     */
    public function __construct(
        public string $setting,
        public string $message,
        public bool $blocking,
    ) {}
}
