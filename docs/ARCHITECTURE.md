# Architecture decisions

## Product invariants
1. WordPress is the platform; presentation and business logic are separate.
2. Booking and financial records must survive theme changes.
3. Version 1 does not collect online payments from customers.
4. New bookings start as `pending_cafe` and require cafe confirmation.
5. Commission defaults to a fixed amount per eligible booking; amount, currency and trigger status (`confirmed` by default, or `completed`) are snapshotted when the booking is created.
6. WhatsApp in v1 is manual-link based. Link opening is not proof of sending or delivery.
7. OTP must use a configured real SMS provider in production; development OTP must never be presented as production authentication.
8. All privileged REST operations must check capabilities and object ownership on the server.

## Components
- **Theme**: public discovery pages, responsive RTL UI and account-facing presentation. No business rules or data ownership.
- **Plugin**: custom database tables, booking lifecycle, capability enforcement, REST API, settings and integration seams.
- **WordPress**: user accounts, media, roles/capabilities, options, REST infrastructure and cron.

## Persistence
The plugin creates versioned custom tables for bookings, append-only finance ledger entries, settlement records and booking audit events. Schema version 10 snapshots the commission trigger status and stores only indexed hashes of one-time check-in tokens per booking. Cafe/event/table content is stored as WordPress custom post types with administrator metadata boxes. Cafes can also be assigned terms in a hierarchical `mtn_cafe_location` taxonomy; administrators should nest country → city → area → neighborhood terms. Booking codes are random and unique. Status changes are validated against allowed transitions; audit events are appended for creation, status changes, table assignment and expiry.

## API namespace
Base namespace: `mtn/v1`. Public read endpoints must return only published public data. Booking creation and private booking reads require authentication. Mutations require a valid WordPress REST nonce/cookie or another supported authenticated mechanism and object-level authorization.

## Reservation concurrency and time semantics
Booking creation obtains a MySQL/MariaDB advisory lock scoped to the cafe before checking capacity and inserting a reservation. A lock timeout returns a retryable service error. Event capacity is a total headcount across `pending_cafe` and `confirmed` bookings attached to that event record; zero means no configured cap. When a customer selects a table, the system rejects another active booking starting within the configured reservation-duration window. The default duration is 120 minutes and is configurable in plugin settings.

The REST API accepts `booking_at` as `YYYY-MM-DD HH:MM:SS` in the WordPress site's configured timezone, rejects past times, and stores the converted UTC value. API consumers must display/submit dates in the site's timezone and interpret returned database timestamps as UTC.

These controls depend on MySQL/MariaDB `GET_LOCK()` support and still need integration/concurrency tests on the supported production database. Cafe-assigned table mode supports manual assignment in the cafe dashboard but not automatic allocation. The five-minute expiry worker uses WP-Cron; configure a real server cron to trigger WordPress cron reliably in production. Full recurring schedules, multi-user cafe teams, automatic notifications and payment integrations remain incomplete. Booking creation supports an optional per-user idempotency key; clients should use one for every create attempt.


## Security
- Sanitize input and escape output.
- Use WordPress capabilities and object ownership checks.
- Use `$wpdb->prepare()` for SQL values.
- Never commit secrets, OTP codes, access tokens or customer data.
- Keep public API responses minimal.
- Rate-limit OTP and booking endpoints before production.
- The current build has not passed an end-to-end or concurrency test suite; syntax CI alone is not a production security review.
