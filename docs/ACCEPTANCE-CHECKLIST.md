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
- [x] Administrator-managed multi-user cafe team assignment and expiring invitation/acceptance workflow for existing WordPress accounts.
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
- [x] PHPUnit and WordPress integration CI cover settings, installation/schema markers, team authorization, cross-customer booking read/history/check-in-token privacy, anonymous booking-list denial, booking confirmation/cancellation with commission reversal, idempotency replay/customer isolation, overlapping-table rejection, successful OTP login/replay, OTP throttling/lockout/resend invalidation, and notification idempotency. The full REST permission matrix and true multi-process capacity race remain incomplete.
- [x] Database advisory-lock contention and event-capacity invariants are tested under serialized competing booking requests on the MySQL 8 and MariaDB 10.11 CI matrix; a true multi-process capacity-race test remains a follow-up.
- [x] CI passed on 2026-10-17 on MySQL 8.0 and MariaDB 10.11 with 21 PHPUnit tests and 104 assertions per engine, including schema installation/version markers, booking lifecycle and commission reversal, idempotency/customer isolation, overlapping-table rejection, SMS OTP authentication/replay, and a database backup/restore round trip. Production-host backup/restore must still be rehearsed.
- [x] CI package/extraction smoke tests passed: plugin/theme ZIPs were installed into clean WordPress test core, schema activation completed, and the theme was discoverable.
- [ ] Accessibility, responsive, security and performance review on supported devices and hosting.
- [ ] Staging sign-off before production release.

The remaining live-environment review and sign-off must use [STAGING-RELEASE-GATE.md](STAGING-RELEASE-GATE.md) and include evidence from the actual hosting stack; source review and CI do not satisfy this gate.
