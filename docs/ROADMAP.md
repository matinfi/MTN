# Delivery roadmap

## Phase 0 — foundation (completed)
- [x] Repository and WordPress plugin/theme separation
- [x] Plugin activation schema and REST route foundation
- [x] Booking code and controlled status transitions
- [x] RTL public theme shell
- [x] Architecture, scope and CI documentation

## Phase 1 — core booking
- [x] Cafe, event and table WordPress content types with administrator metadata forms
- [ ] Geographic taxonomy (country, city, area, neighborhood)
- [x] Basic event headcount limits, selected-table overlap checks and per-cafe database locking (integration/concurrency tests still required)
- [x] Advisory availability API with event date/hour validation and selected-table filtering
- [x] Manual table assignment from the cafe dashboard with locked overlap validation
- [ ] Full schedule-aware capacity allocation and automatic table assignment
- [x] Customer booking wizard, booking history and cancellation UI
- [x] Administrator booking confirmation/rejection console
- [x] Cafe-owner dashboard for owned bookings with confirm/reject/cancel actions
- [ ] Staff invitations, multi-user teams and configurable cancellation policy
- [x] Optional idempotency keys for booking creation
- [ ] Append-only booking audit log

## Phase 2 — finance and attendance
- [ ] Append-only commission ledger and correction/reversal entries
- [ ] Fixed-per-eligible-booking default commission settings and cafe statements
- [ ] Secure time-limited check-in token and audit history
- [ ] Manual settlement records and CSV exports

## Phase 3 — communications and identity
- [ ] Real SMS provider adapter, OTP expiry, resend limits and attempt throttling
- [ ] WhatsApp manual-link templates and truthful action statuses
- [ ] Optional official WhatsApp Business API + webhook adapter in a later release
- [ ] Notification queue, retry policy and duplicate prevention

## Phase 4 — release readiness
- [ ] Unit, integration, REST authorization and concurrency tests
- [ ] Accessibility and responsive QA
- [ ] Clean WordPress install/upgrade/uninstall tests
- [ ] Independent theme/plugin ZIP packaging and release workflow
- [ ] Security review, staging acceptance and operational runbook
