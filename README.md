# Cafe Reservation Platform (MTN)

WordPress-based Persian RTL platform for discovering cafés with live music and night events and requesting table reservations.

## Version 0.7.1 — finance reconciliation summary
This repository contains a WordPress plugin and Persian RTL theme for cafe discovery, live music/night-event listings and table reservation requests. Version 0.7.1 includes a public catalog and advisory availability API, a booking wizard, customer booking history/cancellation, admin metadata fields for cafes/events/tables, an administrator booking console, cafe-owner-scoped booking dashboard, append-only commission ledger, manual settlement and adjustment records, an admin finance reconciliation summary, CSV export, and cafe/event detail/archive templates. Features requiring external credentials (production SMS/OTP and WhatsApp Business) are not represented as live until configured.

## Architecture
- `wp-content/plugins/mtn-cafe-booking/`: business rules, booking persistence, REST API, permissions and settings.
- `wp-content/themes/mtn-cafe-theme/`: presentation only; reservation records remain if the theme changes.
- WordPress APIs, capability checks, REST permission callbacks, nonce support, prepared SQL, and semantic versioning.
- No online customer payments in v1. Default booking state is `pending_cafe`; commission is not revenue collected.
- WhatsApp v1 exposes a prepared manual link in the customer's booking list when the cafe has a valid WhatsApp number; opening the link does not imply sent/delivered.
- OTP production authentication requires a real SMS provider integration and configuration.

## Installation (development)
1. Install a supported WordPress site with PHP 8.1+ and MySQL/MariaDB.
2. Copy `wp-content/plugins/mtn-cafe-booking` to `wp-content/plugins/` and activate **MTN Cafe Booking**.
3. Copy `wp-content/themes/mtn-cafe-theme` to `wp-content/themes/` and activate **MTN Cafe**.
4. Activate the plugin and theme. The plugin creates `/book/`, `/my-account/` and `/cafe-dashboard/` pages when those slugs do not already exist. Cafe/event archives are `/cafes/` and `/events/`.
5. Create and publish a cafe, assign its nested location term (country → city → area → neighborhood) in the Cafe Locations taxonomy, set its city/contact/music metadata and (optionally) select an existing WordPress user as owner. Saving the cafe assigns that user the cafe-manager role. Then create and publish an event, select its host cafe, set capacity/date/start/end time and party limits. Create published table posts, select their host cafe, set a capacity and enable the active checkbox.
6. Configure the site timezone, currency, brand, fixed commission, commission trigger (`confirmed` by default or `completed`), cancellation cutoff and booking policy in WordPress settings/plugin settings.
7. Do not use production customer data until deployment hardening, OTP provider integration and acceptance tests are complete.

## Current scope and limitations
- Implemented: configurable customer cancellation cutoff (0 means cancellation is allowed until booking start); versioned REST APIs for catalog, availability, booking creation/read/cancel/history and admin confirmation/rejection; per-cafe advisory locks; event headcount and schedule checks; selected-table overlap validation; optional idempotency; scheduled expiry of stale pending holds with audit events; customer booking wizard/history; administrator booking console; cafe-manager dashboard scoped to owned cafes; cafe/event/table metadata boxes; responsive RTL listing/detail templates.
- Incomplete: staff invitations and invite acceptance (administrators can assign existing WordPress users as cafe team managers), automatic table allocation (manual assignment is available from the cafe dashboard), production OTP, WhatsApp API delivery, automated integration/concurrency tests and production deployment. The admin reconciliation summary separates accrued commissions, reversals, settlements, adjustments and net outstanding balances by currency; it does not infer overdue amounts because no due-date policy is configured. Secure check-in tokens are implemented for assigned cafe owners, team managers and administrators, but the end-to-end workflow still requires testing. Commission amount, currency, trigger status and cancellation/no-show reversal policy are snapshotted when the booking is created; the default trigger is cafe confirmation, with completion available as an alternative. A ledger entry is not proof that money was collected. Availability is advisory; booking creation is authoritative. Database locking and all upgrade paths still require testing on the target hosting stack.
- Do not treat this foundation as a production-ready reservation engine until concurrency, authorization, integration and acceptance tests pass.

## Development
Run the lightweight static source-contract regression checks (these are not a replacement for integration tests), then lint PHP files:
```bash
php tests/static-contracts.php
find wp-content tests -name '*.php' -print0 | xargs -0 -n1 php -l
node --check wp-content/plugins/mtn-cafe-booking/assets/booking-wizard.js
```

See [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md), [docs/API.md](docs/API.md), [docs/FINANCE.md](docs/FINANCE.md), [docs/TESTING.md](docs/TESTING.md), [docs/DEPLOYMENT.md](docs/DEPLOYMENT.md), [docs/ROADMAP.md](docs/ROADMAP.md), and [CHANGELOG.md](CHANGELOG.md).
