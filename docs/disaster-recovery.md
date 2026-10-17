# Flow Ledger — Backup and Disaster Recovery Runbook

Audience: whoever operates the production Linode. Keep a printed or offline copy; you will need it when the app is down.

Bracketed items like `[ ... ]` are values only you know. Fill them in and keep them current.

## 1. Targets

| Target | Value today | Notes |
|---|---|---|
| **RPO** (max data loss) | **6 hours** | Backups run every 6 hours. Point-in-time recovery (WAL archiving) is on the roadmap and would cut this to minutes. |
| **RTO** (max downtime) | **4 hours (target)** | A target until a full rebuild drill (section 8) proves the real number. Record the measured time here: `[ __ hours, drill date __ ]`. |

## Command reference

| Command | What it does | Where to run it |
|---|---|---|
| `php artisan backup:generate-key` | Prints a new public/private key pair. Run once. | A trusted machine |
| `php artisan backup:run` | Dumps, encrypts and uploads every database, then prunes. `--no-prune` keeps expired backups. Runs every 6 hours by itself. | The app server |
| `php artisan backup:check` | Fails and alerts if any database has no fresh, intact backup. Runs hourly by itself. | The app server |
| `php artisan backup:verify --private-key-file=<file>` | Restores the newest backup of each database into a throwaway database and checks it. `--database=<name>` limits it to one. | A trusted machine with the private key |
| `php artisan backup:decrypt <input> <output> --private-key-file=<file>` | Decrypts a downloaded backup so `pg_restore` can use it. | A trusted machine with the private key |

## 2. What is protected, and where

| Asset | Protection | Location |
|---|---|---|
| Central database (tenants, domains, landlord users, backup records) | Encrypted `pg_dump` every 6 hours | Backups bucket (section 3) |
| One database per tenant | Encrypted `pg_dump` every 6 hours, one file per tenant | Backups bucket |
| Uploaded files (attachments) | **Not yet automatic.** Planned for DigitalOcean Spaces (see section 9) | `[ current location ]` |
| `.env` and `APP_KEY` | Copy in the password manager. Not in git | `[ vault name ]` |
| Private backup key | Offline copy plus password manager. **Never on the server** | `[ locations ]` |
| Code | Git | `[ repository URL ]` |
| Redis (sessions, queue, cache) | Not backed up on purpose. A restore signs users out and drops queued jobs | n/a |

How backups are made: `php artisan backup:run` loops over the central database and every tenant database, dumps each with `pg_dump`, encrypts the file, uploads it, records it in `backup_runs`, then prunes expired copies. Encryption is libsodium (XChaCha20-Poly1305) with a random key per file, sealed to your **public** key. The server can create backups but cannot read them. Only the private key decrypts.

Retention: daily copies for 30 days; the first backup of each month for 12 months. The newest good backup of every database is never pruned. Change these with `BACKUP_RETENTION_DAYS` and `BACKUP_MONTHLY_RETENTION_MONTHS`.

## 3. One-time setup

1. **Create a bucket for backups on a different provider than the Linode** (for example a DigitalOcean Space). Use a separate bucket and separate access keys from the uploads bucket. Give the keys write and list access for this bucket only. If your provider supports versioning or object lock on the bucket, turn it on.
2. **Generate the key pair** on a trusted machine:
   ```bash
   php artisan backup:generate-key
   ```
   Put the printed `BACKUP_ENCRYPTION_PUBLIC_KEY=...` line in the server `.env`. Store the **private key** in the password manager and in a sealed offline copy. If it is lost, every backup is unreadable by anyone.
3. **Set the other variables** in the server `.env` (see `.env.example`): `BACKUP_DISK_KEY`, `BACKUP_DISK_SECRET`, `BACKUP_DISK_REGION`, `BACKUP_DISK_BUCKET`, `BACKUP_DISK_ENDPOINT`.
4. **Make sure the scheduler runs.** Backups are scheduled in `bootstrap/app.php`, so the server needs the standard cron entry:
   ```
   * * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1
   ```
5. **Run the first backup by hand** and read any error: `php artisan backup:run`.

## 4. pg_dump version check (do this before relying on backups)

The `pg_dump` on the machine that runs `backup:run` must have the **same major version as the PostgreSQL server**. A dump made by a newer client can fail to restore on an older server. `backup:run` checks this and refuses to run on a mismatch.

In development, the Docker image `brytey2k/sail-php:8.5-node24` (built from the `dev-images` repository with `POSTGRES_VERSION=18`) ships the PostgreSQL **18** client, and the Compose database service is **`postgres:18-alpine`**, so they match. If you ever change one, change the other. A dump made by a newer client than the server fails to restore with errors like `unrecognized configuration parameter "transaction_timeout"`.

Production runs PostgreSQL installed directly on the Linode, so:

```bash
sudo -u postgres psql -tc "show server_version;"   # e.g. 16.x
pg_dump --version                                   # must show the same major version
```

If the client is missing or a different major version, install the matching one (replace 16 with your server's major version):

```bash
sudo apt install postgresql-client-16
```

If the PHP app runs in a container on the Linode, that container needs the same client. Either rebuild the image with `postgresql-client-<server major>` or point `BACKUP_PG_DUMP_BINARY` and `BACKUP_PG_RESTORE_BINARY` at a matching binary mounted into it. For local development you can also set `BACKUP_ALLOW_CLIENT_VERSION_MISMATCH=true`, but never in production.

Also note the database connection used for backups is the app's default connection. Its user must be able to read every tenant database. If tenant databases use a different role, `backup:run` will report a failure for those databases, so check the first run.

## 5. Monitoring

- `backup:check` runs **hourly**. It fails, and reports the problem to Sentry, if any database has no backup, a backup older than 8 hours (`BACKUP_MAX_AGE_HOURS`), or a newest backup file that is missing or whose size changed.
- `backup:run` reports any failed database to Sentry and exits with an error.
- The landlord **Health** page for each tenant shows the last backup time and last successful restore test.
- Confirm a Sentry alert rule exists for these. `[ confirmed on __ ]`.

## 6. Monthly restore test (mandatory)

A backup nobody has restored is not a backup. Do this monthly, from a trusted machine that has the repo, the private key file, and database access to a **scratch** PostgreSQL server (never production):

```bash
php artisan backup:verify --private-key-file=/secure/path/backup.key
```

For each database this downloads the newest backup, checks its checksum, decrypts it, restores it into a throwaway database, checks the tables, the migrations table, and (for tenants) that users exist, then drops the throwaway database. The result is recorded and shown on the Health page. Add `--database=<name>` for one database.

Record each test: `[ date | who | result ]`.

## 7. Recovery procedures

First, in every scenario: **stop and decide.** Note the time, what is wrong, and who is in charge. Keep a log of every command you run. Tell affected tenants once you know the expected downtime.

### 7a. Get a backup file and decrypt it

```bash
# download from the backups bucket with your S3 tool, e.g. the AWS CLI:
aws s3 cp s3://<bucket>/2026-10/<database>/<file>.dump.enc ./ --endpoint-url <endpoint>

php artisan backup:decrypt ./<file>.dump.enc ./<database>.dump --private-key-file=/secure/path/backup.key
```

The decrypted file is plaintext customer data. Delete it when you are done.

### 7b. One tenant's data was deleted or corrupted (server is healthy)

1. Decrypt the tenant's newest good backup taken **before** the damage (7a).
2. Restore it into a **scratch** database, not the live one:
   ```bash
   createdb tenant_restore_check
   pg_restore --no-owner --dbname=tenant_restore_check ./<database>.dump
   ```
3. Copy the needed rows from the scratch database into the live tenant database, or, if the whole tenant is lost, replace the live database (7c). Tell the tenant what was recovered and what window of data was lost.
4. Drop the scratch database and delete the decrypted file.

### 7c. A database is lost or corrupted

1. Put the app in maintenance mode for that tenant (landlord admin) or the whole app.
2. Decrypt the newest good backup (7a).
3. Restore into a fresh database, then swap it in:
   ```bash
   createdb <database>_new
   pg_restore --no-owner --dbname=<database>_new ./<database>.dump
   # when satisfied, rename the broken one aside and the restored one into place
   psql -c 'ALTER DATABASE "<database>" RENAME TO "<database>_broken"'
   psql -c 'ALTER DATABASE "<database>_new" RENAME TO "<database>"'
   ```
4. Run `php artisan migrate --force` (central) and tenant migrations if the backup predates a release.
5. Log in as a user, open a few records, then lift maintenance mode.
6. Data entered after the backup time is lost (up to 6 hours). Say so to the tenant.

### 7d. The whole Linode is lost

1. Create a new Linode (same OS and PHP stack as before). `[ link to provisioning notes ]`
2. Install PostgreSQL **of the same major version**, PHP, nginx, Redis, and `postgresql-client-<major>`.
3. Deploy the code from git at the last released tag. Retrieve `.env` and `APP_KEY` from the password manager. Without the original `APP_KEY`, encrypted values and sessions cannot be read.
4. Restore the **central** database first (7a, 7c steps 2–3), then each tenant database from the list in the central `tenants` table.
5. Run `php artisan migrate --force`, `php artisan config:cache`, restart the queue worker, and confirm the cron entry exists.
6. Point DNS at the new server. Lower the DNS TTL in advance (in a calm moment) so this is fast: `[ current TTL ]`.
7. Verify: log in, open a recent record, create a test payment request, confirm email and queue work.
8. Run `php artisan backup:run` straight away, then confirm `backup:check` passes. A new server with no backups is the riskiest state.

### 7e. Ransomware or a compromised server

1. Isolate the server. Do not run it further.
2. Build a clean server (7d). Do **not** reuse the old disk.
3. Rotate **every** secret: `APP_KEY` (note: this invalidates sessions and encrypted data written under the old key, so read the Laravel docs on key rotation first), database passwords, the identity provider client secret, mail keys, and both bucket keys.
4. Restore from backups that are older than the earliest sign of compromise. Backups are encrypted with a public key and the private key was never on the server, so an attacker could not read them. Check, though, that they could not delete them: review the bucket's access log and versioning.
5. Treat it as a personal-data breach. See the incident section of `docs/compliance-and-security-overview.md`.

### 7f. Uploaded files lost

Until attachments are on Spaces and replicated (section 9), this depends on `[ current storage and its backup ]`. Database rows still list every attachment, so a restore of the files from any copy lines up with them.

## 8. Rebuild drill (do it once before the demo, then every 6 months)

Do a full 7d rebuild on a throwaway Linode, time it, and record the result as the RTO in section 1. Delete the throwaway server and its data afterwards.

## 9. Known limits and roadmap

- **RPO is 6 hours.** Point-in-time recovery with WAL archiving (for example pgBackRest or WAL-G to the backups bucket) would reduce this to minutes.
- **Single server.** There is no standby. The runbook rebuilds from backups, not failover.
- **Uploads:** the `spaces` disk is configured but attachments still use the local disk in the application code (`Storage::disk('local')` in several services). Moving them needs a code change, plus replication of the bucket.
- **Redis is not backed up.**
- **Region and residency:** `[ Linode region ]` and `[ Spaces region ]` are outside Ghana. Record this in the compliance document.
