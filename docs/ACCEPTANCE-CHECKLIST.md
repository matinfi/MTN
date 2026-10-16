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
- [ ] Idempotency key for retried client requests.
- [ ] Full cancellation policy and expiration worker.
- [ ] Append-only status history with actor, reason and timestamp.

## Cafe operations
- [x] WordPress content models for cafes, events and tables.
- [ ] Cafe owner and staff management screens.
- [ ] Object-level cafe staff authorization.
- [ ] Cafe confirmation/rejection UI and rejection reason.
- [ ] Event schedule, recurrence, media and publication approval.
- [ ] Geo hierarchy (country, city, area, neighborhood).

## Finance
- [x] Central setting for default fixed commission amount and currency.
- [ ] Ledger entry created at the configured eligibility point.
- [ ] Append-only corrections/reversals.
- [ ] Manual settlement records, statements and CSV exports.
- [ ] Reconciliation reports distinguishing accrued, settled and overdue amounts.

## Identity and communication
- [x] Manual WhatsApp URL helper; never reports a link opening as delivery.
- [ ] Real SMS OTP provider, expiry, throttling, resend and attempt limits.
- [ ] Secure time-limited check-in tokens.
- [ ] Check-in audit trail and authorized cafe-staff action.
- [ ] Notification queue, retry/idempotency and message history.
- [ ] Official WhatsApp API/webhook adapter (not required for v1).

## Release quality
- [x] PHP lint workflow.
- [ ] Unit and integration test suites.
- [ ] REST permission and privacy tests.
- [ ] Concurrent booking tests against supported database engines.
- [ ] Clean install, upgrade and backup/restore tests.
- [ ] Installable plugin/theme ZIPs tested on clean WordPress.
- [ ] Accessibility, responsive, security and performance review.
- [ ] Staging sign-off before production release.
