# Cafe Reservation Platform (MTN)

WordPress-based Persian RTL platform for discovering cafés with live music and night events and requesting table reservations.

## Version 0.1.0 — foundation
This repository is the implementation workspace. The initial delivery establishes the plugin/theme boundary, secure versioned REST API, persistent booking records, reservation state transitions, and a lightweight RTL theme. Features that require external credentials (production SMS/OTP and WhatsApp Business) are explicitly not represented as live until configured.

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
4. In Settings → Permalinks, save once if REST routes are not available.
5. Configure site timezone, currency, brand and booking policy in WordPress settings/plugin settings.
6. Do not use production customer data until deployment hardening, OTP provider integration and acceptance tests are complete.

## Current scope and limitations
- Implemented foundation: booking table/schema lifecycle, booking code generation, initial REST endpoints, public event/cafe discovery placeholders based on WordPress content, booking ownership/cafe authorization checks, basic theme templates, CI syntax checks.
- Planned/incomplete: full cafe/event/table administration UI, atomic capacity locking for concurrent requests, full ledger/settlements, production OTP provider, official WhatsApp API/webhooks, comprehensive audit UI, end-to-end tests and production deployment.
- Do not treat this foundation as a production-ready reservation engine until concurrency, authorization, integration and acceptance tests pass.

## Development
Run PHP lint over PHP files:
```bash
find wp-content -name '*.php' -print0 | xargs -0 -n1 php -l
```

See [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md), [docs/ROADMAP.md](docs/ROADMAP.md), and [CHANGELOG.md](CHANGELOG.md).
