# Cafe Reservation Platform (MTN)

WordPress-based Persian RTL platform for discovering cafés with live music and night events and requesting table reservations.

## Version 0.4.0 — finance ledger
This repository contains a WordPress plugin and Persian RTL theme for cafe discovery, live music/night-event listings and table reservation requests. Version 0.4.0 adds a public catalog and advisory availability API, a booking wizard, customer booking history/cancellation, admin metadata fields for cafes/events/tables, an administrator booking console, cafe-owner-scoped booking dashboard, append-only commission ledger, manual settlement and adjustment records, CSV export, and cafe/event detail/archive templates. Features requiring external credentials (production SMS/OTP and WhatsApp Business) are not represented as live until configured.

## Architecture
- `wp-content/plugins/mtn-cafe-booking/`: business rules, booking persistence, REST API, permissions and settings.
- `wp-content/themes/mtn-cafe-theme/`: presentation only; reservation records remain if the theme changes.
- WordPress APIs, capability checks, REST permission callbacks, nonce support, prepared SQL, and semantic versioning.
- No online customer payments in v1. Default booking state is `pending_cafe`; commission is not revenue collected.
- WhatsApp v1 uses prepared text/manual links; opening a WhatsApp link does not imply sent/delivered.
- OTP production authentication requires a real SMS provider integration and configuration.

## Installation (development)
1. Install a supported WordPress site with PHP 8.1+ and MySQL/MariaDB.
2. Copy `wp-content/plugins/mtn-cafe-booking` to `wp-content/plugins/` and activate **MTN Cafe Booking**.
3. Copy `wp-content/themes/mtn-cafe-theme` to `wp-content/themes/` and activate **MTN Cafe**.
4. Activate the plugin and theme. The plugin creates `/book/`, `/my-account/` and `/cafe-dashboard/` pages when those slugs do not already exist. Cafe/event archives are `/cafes/` and `/events/`.
5. Create and publish a cafe, set its city/contact/music metadata and (optionally) select an existing WordPress user as owner. Saving the cafe assigns that user the cafe-manager role. Then create and publish an event with that cafe ID, capacity, date and start/end time. Create published table posts with the same cafe ID, a capacity and the active checkbox.
6. Configure the site timezone, currency, brand and booking policy in WordPress settings/plugin settings.
7. Do not use production customer data until deployment hardening, OTP provider integration and acceptance tests are complete.

## Current scope and limitations
- Implemented: versioned REST APIs for catalog, availability, booking creation/read/cancel/history and admin confirmation/rejection; per-cafe advisory locks; event headcount and schedule checks; selected-table overlap validation; optional idempotency; scheduled expiry of stale pending holds with audit events; customer booking wizard/history; administrator booking console; cafe-manager dashboard scoped to owned cafes; cafe/event/table metadata boxes; responsive RTL listing/detail templates.
- Incomplete: staff invitation/management beyond assigning a cafe owner, automatic table allocation (manual assignment is available from the cafe dashboard), booking status audit history, check-in, production OTP, WhatsApp API delivery, automated integration/concurrency tests and production deployment. Commission is snapshotted when the booking is created and accrued only when it is marked completed; it is a ledger entry, not proof that money was collected. Availability is advisory; booking creation is authoritative. Database locking and all upgrade paths still require testing on the target hosting stack.
- Do not treat this foundation as a production-ready reservation engine until concurrency, authorization, integration and acceptance tests pass.

## Development
Run PHP lint over PHP files:
```bash
find wp-content -name '*.php' -print0 | xargs -0 -n1 php -l
```

See [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md), [docs/API.md](docs/API.md), [docs/FINANCE.md](docs/FINANCE.md), [docs/TESTING.md](docs/TESTING.md), [docs/DEPLOYMENT.md](docs/DEPLOYMENT.md), [docs/ROADMAP.md](docs/ROADMAP.md), and [CHANGELOG.md](CHANGELOG.md).
