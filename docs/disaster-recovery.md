# Flow Ledger — Backup and Disaster Recovery Runbook

Audience: whoever operates Flow Ledger in production. Keep a printed or offline copy; you will need it when the app is down.

Last reviewed: 2026-10-27. Review this runbook after every infrastructure change and at least every 6 months, at the rebuild drill (section 8). Before relying on it, complete the go-live checklist in Appendix B.

## 1. Targets

| Target | Value today | Notes |
|---|---|---|
| **RPO** (max data loss) | **6 hours** | Backups run every 6 hours. Point-in-time recovery (WAL archiving) is on the roadmap and would cut this to minutes. |
| **RTO** (max downtime) | **4 hours (target)** | A target until a full rebuild drill (section 8) proves the real number. Record each measured time in the drill log in section 8. |

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
| Uploaded files (attachments) | Object storage with bucket versioning, so deleted or overwritten files can be recovered | Uploads bucket: DigitalOcean Spaces, `sfo3` (separate bucket and keys from backups) |
| `.env` and `APP_KEY` | Copy in the password manager, updated whenever `.env` changes. Not in git | Password manager vault **Flow Ledger — Production**, entry `Production .env` |
| Private backup key | Three copies, held by two people. **Never on the server** | 1. Password manager vault **Flow Ledger — Production**, entry `Backup private key`.<br>2. Printed copy in a sealed, signed envelope in a fire-resistant safe away from the office.<br>3. Encrypted USB drive kept by the second key holder |
| Code | Git; every production release is a tag | `https://github.com/brytey2k/flow-ledger` |
| Redis (sessions, queue, cache) | Not backed up on purpose. A restore signs users out and drops queued jobs | n/a |

How backups are made: `php artisan backup:run` loops over the central database and every tenant database, dumps each with `pg_dump`, encrypts the file, uploads it, records it in `backup_runs`, then prunes expired copies. Encryption is libsodium (XChaCha20-Poly1305) with a random key per file, sealed to your **public** key. The server can create backups but cannot read them. Only the private key decrypts.

Retention: daily copies for 30 days; the first backup of each month for 12 months. The newest good backup of every database is never pruned. Change these with `BACKUP_RETENTION_DAYS` and `BACKUP_MONTHLY_RETENTION_MONTHS`.

## 3. One-time setup

1. **Create a bucket for backups on a different provider and in a different region from the servers.** Production runs on Linode (Akamai) in Los Angeles, so use a DigitalOcean Space in `nyc3`. Use a separate bucket and separate access keys from the uploads bucket. Give the keys write and list access for this bucket only. Turn on versioning for the bucket.
2. **Generate the key pair** on a trusted machine:
   ```bash
   php artisan backup:generate-key
   ```
   Put the printed `BACKUP_ENCRYPTION_PUBLIC_KEY=...` line in the server `.env`. Store the **private key** in the three places listed in section 2. If it is lost, every backup is unreadable by anyone.
3. **Set the other variables** in the server `.env` (see `.env.example`): `BACKUP_DISK_KEY`, `BACKUP_DISK_SECRET`, `BACKUP_DISK_REGION`, `BACKUP_DISK_BUCKET`, `BACKUP_DISK_ENDPOINT`.
4. **Make sure the scheduler runs.** Backups are scheduled in `bootstrap/app.php`, so the server needs the standard cron entry, owned by the app user (not root):
   ```
   * * * * * cd /var/www/flow-ledger && php artisan schedule:run >> /dev/null 2>&1
   ```
   When there is more than one application server, this cron entry must run on **one** server only.
5. **Run the first backup by hand** and read any error: `php artisan backup:run`.
6. **Lower the DNS TTL** for `flowledger.softwaregh.com` and every tenant domain (the central `domains` table lists them) to **300 seconds**, so a move to a new server takes effect within minutes.

## 4. pg_dump version check (do this before relying on backups)

The `pg_dump` on the machine that runs `backup:run` must have the **same major version as the PostgreSQL server**. A dump made by a newer client can fail to restore on an older server. `backup:run` checks this and refuses to run on a mismatch.

In development, the Docker image `brytey2k/sail-php:8.5-node24` (built from the `dev-images` repository with `POSTGRES_VERSION=18`) ships the PostgreSQL **18** client, and the Compose database service is **`postgres:18-alpine`**, so they match. If you ever change one, change the other. A dump made by a newer client than the server fails to restore with errors like `unrecognized configuration parameter "transaction_timeout"`.

To check a server:

```bash
sudo -u postgres psql -p 5433 -tc "show server_version;"   # e.g. 18.x
pg_dump --version                                          # must show the same major version
```

If the client is missing or a different major version, install the matching one (replace 18 with your server's major version):

```bash
sudo apt install postgresql-client-18
```

If the PHP app runs in a container, that container needs the same client. Either rebuild the image with `postgresql-client-<server major>` or point `BACKUP_PG_DUMP_BINARY` and `BACKUP_PG_RESTORE_BINARY` at a matching binary mounted into it. For local development you can also set `BACKUP_ALLOW_CLIENT_VERSION_MISMATCH=true`, but never in production.

### Production database (as of 2026-10-17)

- Server: Ubuntu 24.04 Linode, shared with other applications.
- Flow Ledger's databases (`flow_ledger_landlord`, `tenant_perseus_2026_06_15`, owner `flow_ledger_user`) run on **PostgreSQL 18.6, port 5433**, a second cluster installed beside an older PostgreSQL 16 cluster (port 5432). Config: `/etc/postgresql/18/main/`, data: `/var/lib/postgresql/18/main/`.
- `DB_PORT` and `DB_TENANT_PORT` in `/var/www/flow-ledger/.env` are `5433`. The previous file is kept as `.env.pre-pg18-20261017-112907`.
- The plain `pg_dump` on the server's PATH is still **version 16** (Debian's wrapper picks the cluster on port 5432). Set `BACKUP_PG_DUMP_BINARY=/usr/lib/postgresql/18/bin/pg_dump` and `BACKUP_PG_RESTORE_BINARY=/usr/lib/postgresql/18/bin/pg_restore` in `.env`; otherwise `backup:run` will refuse to run, because 16 does not match the 18 server.
- Old copies of the Flow Ledger databases remain on the 16 cluster as a rollback. Drop them once you are sure (`sudo -u postgres dropdb -p 5432 <name>`).
- Pre-upgrade dumps are in `/var/backups/flow-ledger-pre-pg18-*` and `/var/backups/flow-ledger-cutover-*` on the server. They contain production data, so delete them when no longer needed.
- The PostGIS extension was deliberately not carried over to 18 for Flow Ledger: it does not use it. When restoring a dump that does contain PostGIS into a new database, leave out the `TABLE DATA public spatial_ref_sys` entry (`pg_restore -l ... | grep -v spatial_ref_sys`, then `-L`), because creating the extension already fills that table.
- Never run `php artisan` as root on the server. Run it as the owner of the project directory.
- **Rollback to 16:** put the app in maintenance mode, stop the three `flow-ledger-worker` processes, restore `.env.pre-pg18-20261017-112907` over `.env`, start the workers and bring the app up. Anything written after the cutover would have to be copied back by hand.

Also note the database connection used for backups is the app's default connection. Its user must be able to read every tenant database. If tenant databases use a different role, `backup:run` will report a failure for those databases, so check the first run.

## 5. Monitoring

- `backup:check` runs **hourly**. It fails, and reports the problem to Sentry, if any database has no backup, a backup older than 8 hours (`BACKUP_MAX_AGE_HOURS`), or a newest backup file that is missing or whose size changed.
- `backup:run` reports any failed database to Sentry and exits with an error.
- The landlord **Health** page for each tenant shows the last backup time and last successful restore test.
- **Sentry alert rule "Backups":** alert when an event's message contains `Backup check failed` or `pg_dump failed`, or when the `backup:run` command errors. Notify the operator and the second key holder by email immediately, and repeat at most once an hour while it keeps firing.
- **External uptime monitor:** poll `https://flowledger.softwaregh.com/health/heartbeat` every 5 minutes. It answers 200 while the scheduler and the queue workers are running and 503 when either has stopped. A stopped scheduler also means no backups, and it cannot report itself, so this monitor is the only thing that catches it. Alert on two failures in a row.

| Alert | Confirmed working on | By |
|---|---|---|
| Sentry "Backups" rule | | |
| Uptime monitor on `/health/heartbeat` | | |

## 6. Monthly restore test (mandatory)

A backup nobody has restored is not a backup. Do this on the **first working Monday of each month**, from a trusted machine that has the repo, the private key file, and database access to a **scratch** PostgreSQL server (never production):

```bash
php artisan backup:verify --private-key-file=/secure/path/backup.key
```

For each database this downloads the newest backup, checks its checksum, decrypts it, restores it into a throwaway database, checks the tables, the migrations table, and (for tenants) that users exist, then drops the throwaway database. The result is recorded and shown on the Health page. Add `--database=<name>` for one database.

Record each test:

| Date | Who | Databases | Result | Notes |
|---|---|---|---|---|
| | | | | |
| | | | | |
| | | | | |
| | | | | |

## 7. Recovery procedures

First, in every scenario: **stop and decide.** Note the time, what is wrong, and who is in charge. Keep a log of every command you run. Tell affected tenants once you know the expected downtime.

### 7a. Get a backup file and decrypt it

```bash
# download from the backups bucket with your S3 tool, e.g. the AWS CLI:
aws s3 cp s3://<bucket>/2026-10/<database>/<file>.dump.enc ./ --endpoint-url https://nyc3.digitaloceanspaces.com

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

### 7d. A whole server is lost

Once additional application servers are in place (section 9), losing one application server is absorbed by the load balancer: remove it from the pool and rebuild it at leisure with Appendix A. This procedure covers losing the database server, or every server.

1. Create a new Linode in the same region, following Appendix A.
2. Install PostgreSQL **of the same major version** (18), PHP 8.5, nginx, Redis, and `postgresql-client-18`.
3. Deploy the code from git at the last released tag. Retrieve `.env` and `APP_KEY` from the password manager. Without the original `APP_KEY`, encrypted values and sessions cannot be read.
4. Restore the **central** database first (7a, 7c steps 2–3), then each tenant database from the list in the central `tenants` table.
5. Run `php artisan migrate --force`, `php artisan config:cache`, restart the queue workers, and confirm the cron entry exists.
6. Point DNS at the new server. With the TTL at 300 seconds (section 3, step 6), users reach it within about 5 minutes.
7. Verify: log in, open a recent record, create a test payment request, confirm email and queue work, and confirm `/health/heartbeat` answers 200.
8. Run `php artisan backup:run` straight away, then confirm `backup:check` passes. A new server with no backups is the riskiest state.

### 7e. Ransomware or a compromised server

1. Isolate the server. Do not run it further.
2. Build a clean server (7d). Do **not** reuse the old disk.
3. Rotate **every** secret: `APP_KEY` (note: this invalidates sessions and encrypted data written under the old key, so read the Laravel docs on key rotation first), database passwords, the identity provider client secret, mail keys, and both bucket keys.
4. Restore from backups that are older than the earliest sign of compromise. Backups are encrypted with a public key and the private key was never on the server, so an attacker could not read them. Check, though, that they could not delete them: review the bucket's access log and versioning.
5. Treat it as a personal-data breach. See the incident section of `docs/compliance-and-security-overview.md`.

### 7f. Uploaded files lost

Uploads live in the uploads bucket with versioning on. To recover deleted or overwritten files, restore their previous object versions (any S3 tool, for example `aws s3api list-object-versions` and `aws s3api copy-object` with `--endpoint-url https://sfo3.digitaloceanspaces.com`). If the whole bucket is lost, restore from the nightly copy in the second region (section 9). Database rows still list every attachment's path, so files restored from any copy line up with them.

## 8. Rebuild drill (do it once before the demo, then every 6 months)

Do a full 7d rebuild on a throwaway Linode, time it from "start" to "a user can log in and open a record", and record the result below. The latest measured time is the RTO in section 1. Delete the throwaway server and its data afterwards.

| Date | Who | Measured time (hours) | Problems found | Fixed in runbook? |
|---|---|---|---|---|
| | | | | |
| | | | | |

## 9. Known limits and roadmap

- **RPO is 6 hours.** Point-in-time recovery with WAL archiving (for example pgBackRest or WAL-G to the backups bucket) would reduce this to minutes.
- **Scaling and failover (in progress).** Additional servers are being added for horizontal scalability and failover. Application servers run behind a load balancer, so the loss of one does not take the service down and capacity grows by adding servers. Uploaded files (object storage) and sessions, cache and queues (Redis) are kept off the application servers' disks, so any server can handle any request. A PostgreSQL standby replica will allow the database to fail over to a second server instead of being rebuilt from backups, which brings the RTO for a server loss down from hours to minutes. Until the standby is live, losing the database server follows 7c/7d.
- **Uploads second copy:** a nightly copy of the uploads bucket to a second region (for example `rclone sync` to `nyc3`), so the loss of the whole bucket is recoverable.
- **Redis is not backed up.**
- **Region and residency:** the servers are in Linode's Los Angeles region (US), uploads in DigitalOcean `sfo3` (US) and backups in DigitalOcean `nyc3` (US). All are outside Ghana. Record this in the compliance document.

## Appendix A. Server build checklist

Use this for the 7d rebuild, the rebuild drill, and every new server added for scaling.

1. Linode, Ubuntu 24.04 LTS, in the Los Angeles region. Enable Linode Backups on the database server as an extra layer.
2. Create the app user; disable root SSH login and password login; allow only ports 22, 80 and 443 (`ufw`).
3. Install PHP 8.5 (FPM and CLI) with the `pgsql`, `redis`, `intl`, `mbstring`, `xml`, `curl`, `zip`, `bcmath` and `gd` extensions; nginx; Redis; Supervisor; Composer; and `postgresql-client-18`.
4. Database server only: install PostgreSQL 18, create `flow_ledger_user`, and restore the databases (7d step 4).
5. Clone `https://github.com/brytey2k/flow-ledger` into `/var/www/flow-ledger` at the last release tag, owned by the app user. Run `composer install --no-dev --optimize-autoloader` and `npm ci && npm run build`.
6. Put `.env` from the password manager in place with mode `640`. Set `BACKUP_PG_DUMP_BINARY` and `BACKUP_PG_RESTORE_BINARY` if `pg_dump` on the PATH is not version 18.
7. Configure nginx for `flowledger.softwaregh.com` and the tenant domains, with TLS certificates (certbot).
8. Configure Supervisor to run three `flow-ledger-worker` queue worker processes.
9. Add the scheduler cron entry (section 3, step 4) on one server only.
10. Run `php artisan config:cache`, `php artisan route:cache` and `php artisan view:cache`, then complete the checks in 7d steps 7–8.

## Appendix B. Go-live checklist

The choices in this runbook are only true once they are set up. Work through this list, write who did each item and when, and keep it with the runbook.

| # | Task | Section | Who | Done on |
|---|---|---|---|---|
| 1 | Confirm in the Linode dashboard that production is in the Los Angeles region. If not, update sections 2, 3, 9 and Appendix A. | 9 | | |
| 2 | Create the uploads bucket (DigitalOcean Spaces `sfo3`) with its own access keys and versioning on, and point production uploads at it. The attachment services use the `local` disk, so its definition in `config/filesystems.php` must point at the bucket. | 2 | | |
| 3 | Create the backups bucket (DigitalOcean Spaces `nyc3`) with versioning on and keys limited to write and list on that bucket. Set `BACKUP_DISK_KEY`, `BACKUP_DISK_SECRET`, `BACKUP_DISK_REGION`, `BACKUP_DISK_BUCKET` and `BACKUP_DISK_ENDPOINT` in the server `.env`. | 3 | | |
| 4 | Set `BACKUP_PG_DUMP_BINARY` and `BACKUP_PG_RESTORE_BINARY` to the PostgreSQL 18 binaries. | 4 | | |
| 5 | Create the password manager vault **Flow Ledger — Production** and save the production `.env` in it as `Production .env`. | 2 | | |
| 6 | Choose the second key holder. Run `php artisan backup:generate-key` on a trusted machine, put the public key in `.env`, and store the private key in all three places. | 2, 3 | | |
| 7 | Run `php artisan backup:run` by hand, read any errors, then confirm `php artisan backup:check` passes. | 3 | | |
| 8 | Lower the DNS TTL to 300 seconds for `flowledger.softwaregh.com` and every tenant domain. | 3 | | |
| 9 | Create the Sentry "Backups" alert rule, trigger it once to test it, and record it in the section 5 table. | 5 | | |
| 10 | Set up the external uptime monitor on `/health/heartbeat`, test it by stopping the workers briefly, and record it in the section 5 table. | 5 | | |
| 11 | Run the first restore test with `backup:verify` and record it. | 6 | | |
| 12 | Run the rebuild drill before the demo and record the measured time as the RTO. | 8 | | |
| 13 | Set up the nightly copy of the uploads bucket to `nyc3`. | 9 | | |
| 14 | Before a second application server goes live: make sure scheduled tasks run on one server only (cron on one server, or `onOneServer()` on each task in `bootstrap/app.php`), and that Redis and the uploads bucket are shared by all servers. | 3, 9 | | |
