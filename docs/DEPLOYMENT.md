# Deployment and staging runbook

## Supported baseline

- WordPress 6.4 or newer
- PHP 8.1 or newer
- MySQL/MariaDB with InnoDB and advisory-lock support (`GET_LOCK` / `RELEASE_LOCK`)
- HTTPS for login, customer sessions and authenticated REST requests
- A valid WordPress timezone configured before publishing event schedules

The repository's CI checks PHP syntax, booking-wizard JavaScript syntax and static source contracts. A WordPress PHPUnit workflow has been added for settings and team authorization tests, but its successful execution and the broader booking/concurrency scenarios must be verified. Browser, load and security reviews remain release gates.

## Install on staging

1. Back up the WordPress database and files.
2. Install the plugin ZIP and activate it; install/activate the theme ZIP separately.
3. Confirm the plugin schema migration reaches version 13 and that the booking, ledger, settlement and audit tables exist.
4. Save Settings → Permalinks once if the cafe/event archive routes return 404.
5. Configure brand, currency, fixed commission and commission trigger (`confirmed` by default or `completed`), booking expiry, reservation duration, customer cancellation cutoff, check-in token expiry, statement due days and table-selection mode. Set statement due days to the agreed finance policy and configure a valid statement email in each cafe's metadata. A zero-minute cancellation cutoff allows customer cancellation until the reservation start; positive values block cancellation inside that many minutes. Currency is intentionally locked after the first booking or ledger entry.
6. Create a cafe and set its city, address, contact details, music genre and optional owner user. Saving an owner assigns the cafe-manager role.
7. Create and publish active tables. Create and publish events with a cafe, date, start time, optional end time, party limits and capacity.
8. Verify /cafes/, /events/, /book/, /login/ and /my-account/. The legacy /cafe-dashboard/ page no longer exists.
9. Run the complete scenario list in docs/TESTING.md, including two or more simultaneous booking requests.

## Dashboard access and customer sign-in

- Only administrators can use wp-admin. Customers and cafe managers sign in on the front-end `/login/` page (created automatically as `/mtn-login/` if `/login/` is already used by another page) and are sent to `/my-account/`.
- Test as a non-admin user: opening `/wp-admin/` must redirect to `/my-account/`, the admin bar must not show, and `/wp-login.php` must redirect to `/login/`. Test password reset from the e-mail link, logout, and an administrator signing in at `/login/`.
- Break-glass options in `wp-config.php`: `MTN_CB_DISABLE_AUTH_REDIRECT` (stop redirecting wp-login.php), `MTN_CB_ALLOW_STAFF_ADMIN` (restore wp-admin for every signed-in user). `wp-login.php?mtn-admin=1` opens the classic form for administrators.
- Exclude `/login/` and `/my-account/` from full-page caching; the sign-in forms use nonces and a cached page shows "session expired" errors.
- Customer registration can be switched off in plugin settings.

## Scheduled expiry

The plugin schedules a five-minute WP-Cron event to expire stale pending bookings and append audit entries. WP-Cron is traffic-driven by default; for production, configure a real system cron to trigger WordPress cron regularly. Follow WordPress's official cron guidance for the hosting environment and disable page caching for authenticated account/dashboard pages.

## Before production

Complete the evidence-based [staging and production release gate](STAGING-RELEASE-GATE.md) before approving release. Do not substitute source inspection or CI results for browser/device, hosting-specific security/performance, provider-delivery, or restore-rehearsal evidence.


- Verify `GET_LOCK` behavior and timeout handling on the actual database/hosting platform.
- Run schema upgrades from versions 1–12 on disposable database copies.
- Validate login, REST nonce behavior, cafe-owner isolation, cancellation, table assignment, finance balance and CSV export permissions.
- Confirm backups and a tested rollback procedure.
- Configure the site mailer before using invitations and notifications. For optional SMS OTP, define `MTN_CB_TWILIO_ACCOUNT_SID`, `MTN_CB_TWILIO_AUTH_TOKEN` and `MTN_CB_TWILIO_FROM` in `wp-config.php` or a secret-injection layer; never commit credentials. Assign each user's E.164 phone to their WordPress account from the administrator profile. Verify live SMS delivery, throttling and login cookies on staging. WhatsApp Business API remains optional for v1.
- Restrict repository visibility to the intended audience before storing internal-only documentation. The repository was observed as public during initial setup.
- Do not advertise online payments or automatic WhatsApp delivery. SMS OTP must not be advertised as live until Twilio credentials are configured and delivery/login behavior is verified on staging.

## Recovery notes

- Deactivation preserves bookings, audit history and finance records; it clears the scheduled expiry event and rewrites.
- Re-activation re-runs schema migrations and re-schedules the expiry worker.
- Do not delete ledger or audit rows to correct mistakes. Add a documented adjustment or restore from a verified backup under an approved recovery procedure.
