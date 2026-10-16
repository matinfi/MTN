# Delivery roadmap

## Phase 0 — foundation (completed)
- [x] Repository and WordPress plugin/theme separation
- [x] Plugin activation schema and REST route foundation
- [x] Booking code and controlled status transitions
- [x] RTL public theme shell
- [x] Architecture, scope and CI documentation

## Phase 1 — core booking
- [x] Cafe, event and table WordPress content types with administrator metadata forms
- [x] Hierarchical cafe-location taxonomy supports country, city, area and neighborhood terms.
- [x] Basic event headcount limits, selected-table overlap checks and per-cafe database locking (integration/concurrency tests still required)
- [x] Advisory availability API with event date/hour validation and selected-table filtering
- [x] Manual table assignment from the cafe dashboard with locked overlap validation
- [ ] Full schedule-aware capacity allocation and automatic table assignment
- [x] Customer booking wizard, booking history and cancellation UI
- [x] Administrator booking confirmation/rejection console
- [x] Cafe-owner dashboard for owned bookings with confirm/reject/cancel actions
- [x] Scheduled expiry worker for stale pending reservations
- [x] Expiring staff invitation and acceptance workflow; multi-user access remains scoped to assigned cafes.
- [x] Optional idempotency keys for booking creation
- [x] Append-only booking audit log for creation, status transitions and table assignment

## Phase 2 — finance and attendance
- [x] Append-only commission ledger and manual correction/reversal entries
- [x] Fixed commission amount/currency and configurable trigger (`confirmed` by default, `completed` optional) snapshotted at booking creation
- [x] Administrator reconciliation summary by cafe/currency (accruals, reversals, settlements, adjustments and net outstanding balance)
- [x] Monthly cafe statement emails, configurable due-date policy and overdue balance report (external bank reconciliation remains manual)
- [x] Secure one-time check-in token, configurable expiry and append-only audit history (end-to-end tests still required)
- [x] Event cancellation commission reversal policy snapshotted per booking
- [x] Manual settlement records and ledger CSV export

## Phase 3 — communications and identity
- [x] Optional Twilio SMS OTP adapter with expiry, resend limits, attempt throttling and WordPress login (live provider configuration/verification required)
- [x] Manual WhatsApp link helper and truthful action status; no delivery claim
- [x] Official WhatsApp Business API/webhook explicitly deferred outside v1 scope
- [x] Email notification queue, bounded retries, message history and duplicate prevention

## Phase 4 — release readiness
- [x] Initial PHPUnit settings and cafe-team authorization tests plus WordPress CI harness
- [ ] Full booking integration, REST privacy and concurrency tests (static source-contract checks are not substitutes).
- [ ] Accessibility and responsive QA
- [ ] Clean WordPress install/upgrade/uninstall tests
- [ ] Independent theme/plugin ZIP packaging and release workflow
- [ ] Security review, staging acceptance and operational runbook
