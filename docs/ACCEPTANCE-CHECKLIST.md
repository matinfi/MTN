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
- [ ] Staff invitation and invite-acceptance workflow.
- [x] Administrator confirmation/rejection console with optional reason.
- [x] Cafe-owner dashboard with confirmation, rejection reason, cancellation and manual table assignment actions.
- [x] Administrator-managed multi-user cafe team assignment for existing WordPress users; invitation workflow remains outstanding.
- [x] Event date, start/end time and capacity metadata fields; booking is blocked until date and start time are valid.
- [ ] Recurrence, media workflow and publication approval.
- [x] Hierarchical cafe-location taxonomy supports nested country, city, area and neighborhood terms; administrators maintain the hierarchy.

## Finance
- [x] Central setting for default fixed commission amount and currency.
- [x] Configurable commission trigger (`confirmed` by default or `completed`); amount, currency and trigger status are snapshotted at booking creation.
- [x] Per-booking snapshot of automatic commission reversal policy for cancellation and no-show after accrual.
- [ ] Commission reversal policy for cancelled events after commission accrual.
- [x] Append-only manual corrections/reversals with actor, timestamp and reason.
- [x] Manual settlement records, cafe balances and ledger CSV export.
- [ ] Automated cafe statements and reconciliation reports distinguishing accrued, settled and overdue amounts.

## Identity and communication
- [x] Manual WhatsApp URL helper; never reports a link opening as delivery.
- [ ] Real SMS OTP provider, expiry, throttling, resend and attempt limits.
- [x] Secure one-time check-in tokens with configurable expiry, hash-only storage and atomic consumption.
- [x] Check-in audit trail and authorized assigned cafe-owner/administrator action; multi-user staff teams remain outstanding.
- [ ] Notification queue, retry/idempotency and message history.
- [ ] Official WhatsApp API/webhook adapter (not required for v1).

## Release quality
- [x] PHP lint workflow.
- [x] Booking wizard JavaScript syntax check in CI.
- [ ] Unit and integration test suites.
- [ ] REST permission and privacy tests.
- [ ] Concurrent booking tests against supported database engines.
- [ ] Clean install, upgrade and backup/restore tests.
- [ ] Installable plugin/theme ZIPs tested on clean WordPress.
- [ ] Accessibility, responsive, security and performance review.
- [ ] Staging sign-off before production release.
