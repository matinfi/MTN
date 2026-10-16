# Architecture decisions

## Product invariants
1. WordPress is the platform; presentation and business logic are separate.
2. Booking and financial records must survive theme changes.
3. Version 1 does not collect online payments from customers.
4. New bookings start as `pending_cafe` and require cafe confirmation.
5. Commission defaults to a fixed amount per eligible booking; the applied rate and amount must be snapshotted when a financial ledger entry is created.
6. WhatsApp in v1 is manual-link based. Link opening is not proof of sending or delivery.
7. OTP must use a configured real SMS provider in production; development OTP must never be presented as production authentication.
8. All privileged REST operations must check capabilities and object ownership on the server.

## Components
- **Theme**: public discovery pages, responsive RTL UI and account-facing presentation. No business rules or data ownership.
- **Plugin**: custom database tables, booking lifecycle, capability enforcement, REST API, settings and integration seams.
- **WordPress**: user accounts, media, roles/capabilities, options, REST infrastructure and cron.

## Persistence
The plugin creates a dedicated bookings table on activation and upgrades it using a schema version. Booking code is random and unique. Status changes are validated against allowed transitions. A later migration will add dedicated tables for cafes, events, tables, assignments, ledger entries, notifications, OTP attempts and audit events.

## API namespace
Base namespace: `mtn/v1`. Public read endpoints must return only published public data. Booking creation and private booking reads require authentication. Mutations require a valid WordPress REST nonce/cookie or another supported authenticated mechanism and object-level authorization.

## Concurrency caveat
A production reservation system must lock or atomically claim capacity in the database and enforce overlap constraints under concurrent traffic. A preflight availability check alone is not sufficient. This first foundation does not yet claim to provide complete race-proof capacity allocation.

## Security
- Sanitize input and escape output.
- Use WordPress capabilities and object ownership checks.
- Use `$wpdb->prepare()` for SQL values.
- Never commit secrets, OTP codes, access tokens or customer data.
- Keep public API responses minimal.
- Rate-limit OTP and booking endpoints before production.
