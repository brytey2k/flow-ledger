# Flow Ledger — Security, Data Protection and Resilience Overview

Prepared for: `[ IT manager / organisation ]`
Prepared by: `[ your name, company ]`
Version / date: `[ v1.0, date ]`

This document describes how Flow Ledger protects customer data today, where it falls short, and what is planned. It separates **what is in place** from **what is planned** so you can judge it on facts. It is not legal advice. Statutory references should be confirmed with your legal counsel.

Items in `[ brackets ]` are details to fill in before sharing.

## 1. Summary

| Area | Status |
|---|---|
| Customer data separation | In place: one database per customer (tenant) |
| Sign-in | In place: single sign-on through an external identity provider; local sign-in available and hardened |
| Access control | In place: role- and permission-based |
| Audit trail | In place: who did what, when, with before/after values for key records |
| Encryption in transit | In place: TLS 1.2 and 1.3 |
| Encrypted offsite backups | In place: every 6 hours, tested by restore |
| Recovery objectives | RPO 6 hours; RTO target 4 hours (to be proven by a rebuild drill) |
| Field-level encryption of sensitive data | Planned |
| MFA | Enforced by the identity provider; not built into local sign-in |
| Independent penetration test | Planned |
| Ghana data protection registration and agreements | See section 8 |

## 2. Architecture and data separation

- Each customer has its **own database**. Queries for one customer cannot read another's tables.
- A central database holds only the list of customers, their domains, platform administrator accounts, and backup records.
- Each customer is reached on its own web address. The application resolves the customer from the address and refuses access to the platform area from a customer address, and the reverse.
- Hosting: `[ provider, region, server size ]`, Linode virtual private server. Database: PostgreSQL installed on the server. Uploaded files: `[ local disk / DigitalOcean Spaces, region ]`.

## 3. Authentication and sessions

**In place**

- **Single sign-on:** customers can delegate sign-in to an external identity provider (OpenID Connect). Tokens are validated by signature (RS256) and issuer. Sign-out from the provider ends the session here too (back-channel logout).
- **Single sign-on hand-off.** The last step of a single sign-in is tied to the browser that began it: the customer's sign-in page keeps a secret in a cookie, and only its hash travels through the identity provider. A sign-in link forwarded to someone else is not honoured silently; that person is shown the account and must confirm, or cancel.
- **Multi-factor authentication** and password rules for single sign-on users are enforced by the identity provider: `[ confirm provider and its MFA policy ]`.
- **Local sign-in** (where enabled for a customer):
  - Passwords must be at least 12 characters with upper and lower case letters, a number and a symbol. In production, passwords found in public breach lists are also rejected.
  - A live checklist on the password screens shows each rule as the user types.
  - Users never receive a password from an administrator. A new user is invited by email and sets their own password through a one-time link.
  - Sign-in attempts are limited to 5 per minute per IP address. An account is locked for 15 minutes after 10 failed attempts from any source; the user is emailed and the event is logged. A successful password reset lifts the lock.
- **Sessions:** an idle session ends after 60 minutes. Two minutes before that, the user sees a warning with the choice to stay signed in or sign out. In production, session data is encrypted and cookies are marked secure and HTTP-only.
- **CSRF protection** is on for all forms. Administrators can force a user to sign out.

**Not in place**

- MFA for local sign-in (the app does not generate or check one-time codes itself).
- A device-recognition alert ("new sign-in from an unknown device").

## 4. Access control

- Access is granted through **roles and permissions** per customer. Features and reports check the specific permission, not just "logged in".
- Customers manage their own roles. The platform team manages the platform administrator accounts separately: a single sign-on identity without a customer is treated as a platform administrator only if it already exists as a platform administrator account here. Accounts are created deliberately by the platform team and are never created on first sign-in.
- A user can only create accounts, assign roles, or edit the email or roles of another user within the permissions they hold themselves.
- Approval workflows enforce who may approve each stage of a request, with stage-level controls.
- Platform administrators running data tools on a customer (for example exports and clean-ups) are recorded in a platform audit log with who ran them, with what parameters and with what outcome.

## 5. Audit trail

- Key actions are recorded with the user, the time, the record, and the old and new values: users, roles, branches, settings, approval workflows, retirements, cash counts and documents.
- Customers with the right permission can view the **Activity Log** and run and export an **Audit Trail report**.
- Account lockouts are written to the activity log.
- **Retention: 7 years** for the customer activity log (`activitylog.retention_years`). The platform audit log of administrator actions is kept indefinitely.
- **Entries cannot be edited or deleted through the application.** The application can only add to the activity log. A finished platform audit entry is locked, and none can be deleted.
- **The one clean-up path** is the platform administrator's "Clear activity log" tool. It removes only entries older than 7 years, whatever dates the administrator enters, and the run itself is recorded in the platform audit log. Clearing a customer's requests with the data tools also leaves the activity for those requests in place until it is 7 years old.
- **Limit:** these protections are in the application. Someone with direct database access could still change rows. A database-level block and tamper-evident chaining are on the roadmap (section 12).

## 6. Protecting data

**In transit.** All web traffic uses TLS 1.2 or 1.3. Production responses send HSTS so browsers insist on HTTPS. Responses also carry protective headers that stop the site being framed by other sites, stop file-type guessing, limit referrer leakage, switch off unused browser features, and restrict where forms can post. Uploaded document previews are served in a locked-down sandbox.

**At rest.**
- Database and server disks: `[ confirm provider disk encryption, or state that it is not enabled ]`.
- Backups are encrypted **before they leave the server**.
- Fields such as phone numbers and bank or mobile-money numbers are not individually encrypted yet. **Planned.**

**Uploads.** File type and size are checked on upload. Files are only served to signed-in users with access to the record.

## 7. Backup and disaster recovery

Full detail is in `docs/disaster-recovery.md`. In short:

- Every 6 hours the platform database and each customer database is dumped, **encrypted with a public key whose private key is never on the server**, and uploaded to a bucket at a different provider. A server compromise cannot expose or decrypt the backups.
- Backups are kept for 30 days, with one per month kept for 12 months. The newest good backup of every database is always kept.
- An hourly check raises an alert if any database has no recent backup or a backup file is missing or altered.
- A restore test downloads, verifies the checksum, decrypts, restores into a throwaway database and checks the result. Each test is recorded and displayed per customer.
- **Targets:** RPO 6 hours, RTO 4 hours (target, to be confirmed by a full rebuild drill: `[ drill date, measured time ]`).
- **Limits:** a single server with no hot standby; recovery rebuilds from backups. Point-in-time recovery (to cut the data-loss window to minutes) is planned.

## 8. Ghana legal and regulatory position

This section maps the platform to Ghana's main data and cyber laws. Confirm details and section numbers with legal counsel.

### Data Protection Act, 2012 (Act 843)

| Principle | Where we are |
|---|---|
| Accountability | `[ name the data protection officer / responsible person ]` |
| Lawfulness and consent | The customer organisation decides what data it enters and why. Consent and notices to its staff are the customer's to give. `[ provide a template if useful ]` |
| Purpose specification | Data is used only to run the customer's payment, retirement and cash workflows |
| Quality of information | Customers can correct records through the application |
| Openness | `[ privacy notice URL ]` |
| Security safeguards | Sections 3 to 7 |
| Data subject participation | Access and correction requests are handled by the customer; we can export a customer's data on request (platform data export tool). A documented process: `[ to write ]` |

Additional points:

- **Roles.** In most cases the customer is the *data controller* and Flow Ledger's operator is the *processor*. A **data processing agreement** with each customer is needed: `[ status ]`.
- **Registration.** Data controllers must register with the **Data Protection Commission**. Registration status: `[ yours and, if applicable, the customer's ]`.
- **Breach notification.** A security compromise must be reported to the Commission and the people affected. Our process is in section 9.
- **Cross-border transfer.** Hosting and backups are outside Ghana: `[ Linode region ]`, `[ bucket provider and region ]`. Record the legal basis and safeguards for this, and tell customers. `[ to confirm with counsel ]`
- **Retention and deletion.** Activity log: 7 years, then removable (section 5). Backups: 30 days daily and 12 months monthly (section 7), so deleted data can remain in backups for up to 12 months. Other data types: `[ define per data type ]`. Deleting a person's data does not remove their activity log entries within the 7 years, as these are kept as the audit record of financial approvals; state this lawful basis in the privacy notice. An offboarding process that deletes a customer's data and its backups after the agreed period is `[ to write ]`.

### Cybersecurity Act, 2020 (Act 1038)

- Cyber incidents are reported to the **Cyber Security Authority**. Our incident process (section 9) includes this step.
- If the customer is a regulated financial institution, Bank of Ghana cybersecurity requirements may apply to them and flow down to us as a service provider: `[ ask the customer ]`.

### Electronic Transactions Act, 2008 (Act 772)

Approvals and records are kept electronically with identifying user, time and content, which supports their use as electronic records. `[ confirm with counsel for evidentiary use ]`.

## 9. Incident response

Draft process, to be adopted formally: `[ owner ]`

1. **Detect and contain:** isolate affected systems, preserve logs.
2. **Assess:** what data, which customers, since when.
3. **Notify:** affected customers promptly; the Data Protection Commission and the Cyber Security Authority as the law requires; affected individuals where required.
4. **Recover:** rebuild from clean images and restore from backups (runbook, section 7e).
5. **Review:** root cause, fix, and a written report.

A written, rehearsed version of this plan is `[ planned ]`.

## 10. Monitoring and operations

- Application errors are sent to Sentry.
- A health route exists for uptime checks. External uptime monitoring: `[ tool, planned ]`.
- A platform **Overview** page lists every customer with stuck work, failed jobs and backup and restore-test status, and flags the ones that need attention. Readings are taken every 10 minutes and stored centrally, so the page is fast however many customers there are. Backup problems are shown at the top.
- A per-customer health view shows stuck approvals, failed background jobs, storage use, and backup and restore-test dates.
- Background job failures can be inspected and retried by platform administrators.
- Automated tests (about 2,000) and a code-style check run on every change in continuous integration; static analysis (PHPStan) is run during development.

## 11. Vulnerability and change management

- Code is in version control with a continuous-integration workflow.
- Dependencies are scanned with `composer audit` and `npm audit`. Both reported no known advisories on 2026-10-17, after updating the affected packages. Re-run them before each release and before sharing this document.
- A code-level security review of the application code (`app`, `routes`, `config`) was run on 2026-10-17 by a team of automated reviewers, each finding challenged by an independent panel. It found 31 issues, mostly authorization gaps. All are fixed and covered by tests. This is not a penetration test.
- Independent **penetration test:** planned, `[ vendor, date ]`.

## 12. Known gaps and roadmap

| Gap | Plan | Target date |
|---|---|---|
| Penetration test | Commission an external test | `[ ]` |
| Field-level encryption of sensitive fields | Encrypt phone, bank and mobile-money numbers | `[ ]` |
| Point-in-time recovery | WAL archiving to the backup bucket | `[ ]` |
| Local-sign-in MFA | One-time codes | `[ ]` |
| Uploads on Spaces with replication | Code change plus bucket replication | `[ ]` |
| Mobile Money and bank integrations, GRA tax features | Scope with customer | `[ ]` |
| API documentation | Generate an OpenAPI specification | `[ ]` |
| Written policies: retention, incident response, DPA, privacy notice | Draft and adopt | `[ ]` |
| Database-level block on editing or deleting audit entries; tamper-evident hash chain | Trigger on the audit tables, then chaining with a verify command | `[ ]` |
| Content-Security-Policy for scripts | Remove inline scripts, then restrict script sources | `[ ]` |

## 13. Questions you may be asked, and honest answers

- **"Where is our data?"** In a PostgreSQL database for your organisation only, on `[ provider/region ]`. Encrypted backups are in `[ bucket provider/region ]`.
- **"Can you read our backups?"** Not from the server. They are encrypted with a key held offline.
- **"What if the server burns down?"** We rebuild from the latest backup, with up to 6 hours of data lost, in about `[ measured time ]`. The procedure is written and tested.
- **"Do you support MFA?"** Through your identity provider, yes. Local sign-in does not have it yet.
- **"Who can see what?"** Only roles and permissions you assign; every sensitive action is logged.
- **"Can the audit log be changed or wiped?"** Not through the application: entries can only be added, and the only clean-up tool removes entries older than 7 years and is itself logged. Direct database access is the remaining gap, listed in section 12.
- **"How long do you keep the audit trail?"** 7 years.
- **"Have you been pen-tested?"** Not yet; it is scheduled for `[ date ]`.
- **"Does it comply with Act 843?"** The technical safeguards are in place. Registration, agreements and policies listed in sections 8 and 12 are what remain.

## 14. Evidence to show in a demo

- The activity log and audit trail report.
- The password screen with the live checklist, and an account lockout.
- The session timeout warning.
- Browser developer tools showing the security headers.
- The Health page with last backup and last restore test.
- The restore test output (`php artisan backup:verify`).
- The disaster recovery runbook.
