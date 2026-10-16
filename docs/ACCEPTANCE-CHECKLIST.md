# Acceptance checklist

This checklist is the release gate. An unchecked item must not be described as implemented or production-ready.

## Booking
- [x] New booking requests default to `pending_cafe`.
- [x] Booking IDs use a random public booking code and a separate internal ID.
- [x] Customer read/cancel endpoints scope records to the authenticated owner.
- [x] Event and cafe must be published records and their relationship must match.
- [x] Table selection validates cafe association and configured capacity.
- [x] Serialize booking writes per cafe using MySQL/MariaDB advisory locks.
- [x] Enforce event headcount capacity and prevent overlapping reservations for a selected table.
- [x] Optional per-user idempotency key with payload matching and duplicate-request recovery.
- [x] Scheduled five-minute expiry worker for stale pending reservations.
- [x] Configurable customer cancellation cutoff in minutes; the REST API rejects late or past-start customer cancellations.
- [x] Append-only booking event history with actor, reason/details and timestamp.

## Cafe operations
- [x] WordPress content models for cafes, events and tables.
- [x] Cafe owner ID and contact metadata fields for administrator configuration.
- [x] Assign the cafe-manager role when an administrator saves an existing user as cafe owner.
- [x] Cafe owner assignment through the cafe metadata editor.
- [x] Object-level authorization for the configured cafe owner on booking read/action routes.
- [x] Staff invitation and invite-acceptance workflow: administrator-only invitation API, 48-hour token, hash-only storage, account-email matching and single-use acceptance.
- [x] Administrator confirmation/rejection console with optional reason.
- [x] Cafe-owner dashboard with confirmation, rejection reason, cancellation and manual table assignment actions.
- [x] Administrator-managed multi-user cafe team assignment for existing WordPress users; invitation workflow remains outstanding.
- [x] Event date, start/end time and capacity metadata fields; booking is blocked until date and start time are valid.
- [x] Weekly/monthly recurrence generates draft occurrences for review; content and featured images are inherited, and WordPress publication states provide editorial approval.
- [x] Hierarchical cafe-location taxonomy supports nested country, city, area and neighborhood terms; administrators maintain the hierarchy.

## Finance
- [x] Central setting for default fixed commission amount and currency.
- [x] Configurable commission trigger (`confirmed` by default or `completed`); amount, currency and trigger status are snapshotted at booking creation.
- [x] Per-booking snapshot of automatic commission reversal policy for cancellation and no-show after accrual.
- [x] Per-booking event-cancellation reversal policy snapshot; unpublishing a published event appends an idempotent commission reversal when enabled.
- [x] Append-only manual corrections/reversals with actor, timestamp and reason.
- [x] Manual settlement records, cafe balances and ledger CSV export.
- [x] Automated monthly cafe statements are queued by cafe/currency with accrued, reversed, settled, adjusted and closing-balance totals; a configurable due-date policy powers overdue-balance reporting. External bank-statement reconciliation remains manual.

## Identity and communication
- [x] Manual WhatsApp URL helper; never reports a link opening as delivery.
- [x] Optional Twilio SMS OTP adapter with five-minute expiry, 60-second resend delay, per-phone/IP limits, five-attempt lockout and OTP WordPress login. Live credentials and delivery verification remain deployment gates.
- [x] Secure one-time check-in tokens with configurable expiry, hash-only storage and atomic consumption.
- [x] Check-in audit trail and authorization for assigned cafe owners, invited team members and administrators.
- [x] Email notification queue with idempotency keys, bounded exponential retries and persistent sent/failed message history; actual delivery depends on site mail configuration.
- [x] Official WhatsApp API/webhook adapter explicitly deferred outside v1 scope; manual click-to-chat remains available and no automatic delivery is claimed.

## Release quality
- [x] PHP lint workflow.
- [x] Booking wizard JavaScript syntax check in CI.
- [x] Static source-contract regression checks in lint and package workflows.
- [x] Initial PHPUnit settings and cafe-team authorization tests plus a WordPress test-suite CI workflow. Full booking lifecycle and concurrency coverage remain outstanding.
- [ ] Comprehensive REST permission and privacy tests, including cross-customer booking access and OTP abuse scenarios.
- [ ] Concurrent booking tests against supported database engines.
- [x] CI coverage configured for plugin schema installation, repeated schema-version upgrades, and a database backup/restore round trip on MySQL 8 and MariaDB 10.11. The workflow result still must pass and production-host backup/restore must be rehearsed.
- [x] CI packages and extracts plugin/theme ZIPs into a clean WordPress test core, checks plugin schema activation and theme discoverability. The successful CI result must be confirmed before release.
- [ ] Accessibility, responsive, security and performance review on supported devices and hosting.
- [ ] Staging sign-off before production release.
