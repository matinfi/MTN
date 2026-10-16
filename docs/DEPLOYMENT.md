# Deployment and staging runbook

## Supported baseline

- WordPress 6.4 or newer
- PHP 8.1 or newer
- MySQL/MariaDB with InnoDB and advisory-lock support (`GET_LOCK` / `RELEASE_LOCK`)
- HTTPS for login, customer sessions and authenticated REST requests
- A valid WordPress timezone configured before publishing event schedules

The repository's CI currently checks PHP syntax and the booking wizard JavaScript syntax. It does not run WordPress integration tests, browser tests, load tests or a security review.

## Install on staging

1. Back up the WordPress database and files.
2. Install the plugin ZIP and activate it; install/activate the theme ZIP separately.
3. Confirm the plugin schema migration reaches version 9 and that the booking, ledger, settlement and audit tables exist.
4. Save Settings → Permalinks once if the cafe/event archive routes return 404.
5. Configure brand, currency, fixed commission, booking expiry, reservation duration, customer cancellation cutoff and table-selection mode. A zero-minute cancellation cutoff allows customer cancellation until the reservation start; positive values block cancellation inside that many minutes. Currency is intentionally locked after the first booking or ledger entry.
6. Create a cafe and set its city, address, contact details, music genre and optional owner user. Saving an owner assigns the cafe-manager role.
7. Create and publish active tables. Create and publish events with a cafe, date, start time, optional end time, party limits and capacity.
8. Verify /cafes/, /events/, /book/, /my-account/ and /cafe-dashboard/.
9. Run the complete scenario list in docs/TESTING.md, including two or more simultaneous booking requests.

## Scheduled expiry

The plugin schedules a five-minute WP-Cron event to expire stale pending bookings and append audit entries. WP-Cron is traffic-driven by default; for production, configure a real system cron to trigger WordPress cron regularly. Follow WordPress's official cron guidance for the hosting environment and disable page caching for authenticated account/dashboard pages.

## Before production

- Verify `GET_LOCK` behavior and timeout handling on the actual database/hosting platform.
- Run schema upgrades from versions 1–8 on disposable database copies.
- Validate login, REST nonce behavior, cafe-owner isolation, cancellation, table assignment, finance balance and CSV export permissions.
- Confirm backups and a tested rollback procedure.
- Configure real email/SMS/WhatsApp providers only after credentials and privacy requirements are approved. Never commit credentials to Git.
- Restrict repository visibility to the intended audience before storing internal-only documentation. The repository was observed as public during initial setup.
- Do not advertise online payments, automatic WhatsApp delivery or SMS OTP as active features in this release.

## Recovery notes

- Deactivation preserves bookings, audit history and finance records; it clears the scheduled expiry event and rewrites.
- Re-activation re-runs schema migrations and re-schedules the expiry worker.
- Do not delete ledger or audit rows to correct mistakes. Add a documented adjustment or restore from a verified backup under an approved recovery procedure.
