<?php

declare(strict_types=1);

namespace App\Support\Deployment;

use Closure;
use Illuminate\Cache\TaggableStore;
use Illuminate\Support\Facades\Cache;

/**
 * Checks the running configuration for settings that are fine on a laptop but unsafe or broken on a server: a
 * development .env copied into production, a missing secret, a mailer that never sends, backups that cannot run.
 *
 * For a production deploy every issue blocks unless it is only a warning (noisy logs, no error tracking). For any
 * other deploy (staging) issues are reported but only the ones that break the app outright block.
 */
final class ConfigurationAudit
{
    /** Host names that only resolve on a developer's machine. */
    private const array DEVELOPMENT_HOSTS = ['localhost', '127.0.0.1'];

    public function __construct(private readonly DatabaseRole $databaseRole) {}

    /** @return list<ConfigurationIssue> */
    public function issues(bool $production): array
    {
        $issues = [];
        $add = function (string $setting, string $message, bool|null $blocking = null) use (&$issues, $production): void {
            $issues[] = new ConfigurationIssue($setting, $message, $blocking ?? $production);
        };

        if (config('app.key') === null || config('app.key') === '') {
            $add('APP_KEY', 'APP_KEY is empty: sessions, encrypted settings and signed links cannot work.', true);
        }

        $this->auditDatabaseRole($add);

        if (! Cache::getStore() instanceof TaggableStore) {
            $add('CACHE_STORE', 'The cache store does not support tags; tenant cache isolation needs redis or memcached.', true);
        }

        $environment = config()->string('app.env');
        if ($production && $environment !== 'production') {
            $add('APP_ENV', "APP_ENV is \"{$environment}\": secure cookies, session encryption and breached-password checks only default on in production.");
        }

        if (config()->boolean('app.debug')) {
            $add('APP_DEBUG', 'APP_DEBUG is on: error pages show stack traces, configuration and query details to anyone.');
        }

        $url = config()->string('app.url');
        if (! str_starts_with($url, 'https://')) {
            $add('APP_URL', 'APP_URL is not https: links in emails (password resets, invitations, approvals) go out over plain HTTP.');
        }

        $this->auditCentralDomain($add, $url);

        if (config('session.secure') !== true) {
            $add('SESSION_SECURE_COOKIE', 'Session cookies are not marked Secure: they would also be sent over plain HTTP.');
        }

        if (config('session.encrypt') !== true) {
            $add('SESSION_ENCRYPT', 'Session data is stored unencrypted.');
        }

        if (in_array(config('mail.default'), ['log', 'array'], true)) {
            $add('MAIL_MAILER', 'MAIL_MAILER is "' . config()->string('mail.default') . '": invitations, password resets and approval emails are never delivered.');
        }

        if (blank(config('mail.from.address')) || config('mail.from.address') === 'hello@example.com') {
            $add('MAIL_FROM_ADDRESS', 'MAIL_FROM_ADDRESS is not set to your own address: providers reject or junk mail from it.');
        }

        if (config('queue.default') === 'sync') {
            $add('QUEUE_CONNECTION', 'QUEUE_CONNECTION is "sync": notifications and other queued work would run inside web requests, and a failing email would fail the request.');
        }

        $this->auditBackups($add);
        $this->auditSso($add);
        $this->auditTenantFilesDisk($add);

        $logChannels = $this->activeLogChannels();
        if (in_array('debug', array_map(fn(string $channel): mixed => config("logging.channels.{$channel}.level"), $logChannels), true)) {
            $add('LOG_LEVEL', 'LOG_LEVEL is "debug": logs carry request details and grow quickly. Use warning or error.', false);
        }

        if (in_array('single', $logChannels, true)) {
            $add('LOG_STACK', 'Logs go to the "single" channel: one file that is never rotated. Use daily (or stderr).', false);
        }

        if (blank(config('sentry.dsn'))) {
            $add('SENTRY_LARAVEL_DSN', 'No Sentry DSN: errors, failed backups and failed backup checks are only in the log files.', false);
        }

        return $issues;
    }

    /**
     * One role creates every organisation's database and reaches it, so it must be able to create databases, and it
     * should not be able to do anything more.
     *
     * @param Closure(string, string, bool|null=): void $add
     */
    private function auditDatabaseRole(Closure $add): void
    {
        $role = $this->databaseRole->privileges();

        if ($role === null) {
            $add('DB_HOST / DB_USERNAME / DB_PASSWORD', 'Cannot connect to the central database.', true);

            return;
        }

        if (! $role['createdb']) {
            $add('DB_USERNAME', "The database role \"{$role['name']}\" cannot create databases: new organisations cannot be set up. Grant it CREATEDB.");
        }

        if ($role['superuser']) {
            $add('DB_USERNAME', "The database role \"{$role['name']}\" is a superuser: anyone who can run SQL as the app can read server files and every database. Use a role with only LOGIN and CREATEDB.", false);
        }
    }

    /**
     * Each organisation is reached at {id}.CENTRAL_APP_URL, and the landlord console and public pages at
     * CENTRAL_APP_URL itself, so it must be the real domain APP_URL points at.
     *
     * @param Closure(string, string, bool|null=): void $add
     * @param string $url
     */
    private function auditCentralDomain(Closure $add, string $url): void
    {
        $central = config()->string('app.central_url');
        $host = parse_url($url, PHP_URL_HOST);

        if (in_array($central, self::DEVELOPMENT_HOSTS, true) || str_ends_with($central, '.test') || str_ends_with($central, '.localhost')) {
            $add('CENTRAL_APP_URL', "CENTRAL_APP_URL is \"{$central}\", a development domain: organisations' addresses ({id}.{$central}) will not resolve.");
        } elseif ($host !== $central) {
            $add('CENTRAL_APP_URL / APP_URL', "APP_URL points at \"{$host}\" but CENTRAL_APP_URL is \"{$central}\": links in emails would not reach the app.");
        }
    }

    /**
     * Encrypted offsite backups of the central and every tenant database (see docs/disaster-recovery.md).
     *
     * @param Closure(string, string, bool|null=): void $add
     */
    private function auditBackups(Closure $add): void
    {
        if (blank(config('backup.public_key'))) {
            $add('BACKUP_ENCRYPTION_PUBLIC_KEY', 'BACKUP_ENCRYPTION_PUBLIC_KEY is empty: backup:run cannot encrypt, so no database is backed up.');
        }

        $disk = config()->string('backup.disk');
        $driver = config("filesystems.disks.{$disk}.driver");

        if (! is_string($driver)) {
            $add('BACKUP_DISK', "BACKUP_DISK is \"{$disk}\", which is not a disk in config/filesystems.php: backups cannot be uploaded.");

            return;
        }

        if ($driver === 'local') {
            $add('BACKUP_DISK', "Backups go to the \"{$disk}\" disk on this server: they would be lost with it. Use an offsite bucket.");

            return;
        }

        $missing = array_filter(
            ['key' => 'BACKUP_DISK_KEY', 'secret' => 'BACKUP_DISK_SECRET', 'bucket' => 'BACKUP_DISK_BUCKET'],
            fn(string $variable, string $setting): bool => blank(config("filesystems.disks.{$disk}.{$setting}")),
            ARRAY_FILTER_USE_BOTH,
        );

        if ($missing !== []) {
            $add(implode(' / ', $missing), "The \"{$disk}\" backup disk is missing its " . implode(', ', array_keys($missing)) . ': backups cannot be uploaded.');
        }

        if (blank(config("filesystems.disks.{$disk}.root"))) {
            $add('BACKUP_DISK_ROOT', "Backups go to the root of the \"{$disk}\" bucket, where they mix with any other app's backups in it. Set BACKUP_DISK_ROOT to this app's own folder.");
        }
    }

    /**
     * Single sign-on through the identity provider. Optional, but once a client id is set the rest must be too.
     *
     * @param Closure(string, string, bool|null=): void $add
     */
    private function auditSso(Closure $add): void
    {
        if (blank(config('sso.client_id'))) {
            $add('SSO_CLIENT_ID', 'SSO is not configured: "Sign in with SSO" and identity-provider provisioning will fail if anyone reaches them.', false);

            return;
        }

        if (blank(config('sso.client_secret'))) {
            $add('SSO_CLIENT_SECRET', 'SSO_CLIENT_ID is set but SSO_CLIENT_SECRET is empty: SSO sign-ins will fail.');
        }

        foreach (['idp_url' => 'SSO_IDP_URL', 'redirect_uri' => 'SSO_REDIRECT_URI', 'jwks_uri' => 'SSO_JWKS_URI'] as $setting => $variable) {
            if (! str_starts_with(config()->string("sso.{$setting}"), 'https://')) {
                $add($variable, "{$variable} is not https: sign-in tokens and keys would travel over plain HTTP.");
            }
        }

        if (! config()->boolean('sso.verify_ssl')) {
            $add('SSO_VERIFY_SSL', 'SSO_VERIFY_SSL is off: the identity provider\'s certificate is not checked, so anyone in the middle can forge sign-ins.');
        }
    }

    /**
     * Attachments are organisations' own receipts and documents: they must stay private and per tenant.
     *
     * @param Closure(string, string, bool|null=): void $add
     */
    private function auditTenantFilesDisk(Closure $add): void
    {
        $disks = config()->array('tenancy.filesystem.disks');

        if (! in_array('local', $disks, true)) {
            $add('tenancy.filesystem.disks', 'The "local" disk, where attachments are stored, is not in tenancy.filesystem.disks: every organisation\'s files would share one folder.', true);
        }

        if (config('filesystems.disks.local.visibility') === 'public') {
            $add('filesystems.disks.local', 'The "local" disk, where attachments are stored, is public: receipts and documents would be readable without signing in.', true);
        }

        if (config('filesystems.disks.local.driver') === 'local') {
            $add('filesystems.disks.local', 'Attachments are on this server\'s disk: back up storage/ with the databases, and move them to a bucket before adding a second app server.', false);
        }
    }

    /** @return list<string> The default log channel, or the channels it stacks. */
    private function activeLogChannels(): array
    {
        $default = config()->string('logging.default');

        if (config("logging.channels.{$default}.driver") !== 'stack') {
            return [$default];
        }

        $stacked = config("logging.channels.{$default}.channels", []);

        return is_array($stacked) ? array_values(array_filter($stacked, is_string(...))) : [];
    }
}
