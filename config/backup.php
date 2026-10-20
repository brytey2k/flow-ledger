<?php

declare(strict_types=1);

return [
    /*
    | Filesystem disk (see config/filesystems.php) that receives the encrypted database backups.
    */
    'disk' => env('BACKUP_DISK', 'backups'),

    /*
    | Base64 public key used to encrypt every backup. The matching private key is never kept on
    | the server, so a compromised server cannot read its own backups. Generate a pair with
    | `php artisan backup:generate-key`.
    */
    'public_key' => env('BACKUP_ENCRYPTION_PUBLIC_KEY'),

    'pg_dump_binary' => env('BACKUP_PG_DUMP_BINARY', 'pg_dump'),
    'pg_restore_binary' => env('BACKUP_PG_RESTORE_BINARY', 'pg_restore'),
    // Backups refuse to run when pg_dump's major version differs from the server's. Set only if you know why.
    'allow_client_version_mismatch' => (bool) env('BACKUP_ALLOW_CLIENT_VERSION_MISMATCH', false),
    'dump_timeout_seconds' => (int) env('BACKUP_DUMP_TIMEOUT', 1800),

    /*
    | Daily backups are kept for this many days; the first backup of each month is kept for
    | monthly_retention_months so older restore points survive.
    */
    'retention_days' => (int) env('BACKUP_RETENTION_DAYS', 30),
    'monthly_retention_months' => (int) env('BACKUP_MONTHLY_RETENTION_MONTHS', 12),

    /*
    | backup:check reports a database as stale when its newest good backup is older than this.
    */
    'max_age_hours' => (int) env('BACKUP_MAX_AGE_HOURS', 8),
];
