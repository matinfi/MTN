# Delivery roadmap

## Product decision baseline — 2026-10-17

Source of truth: [V1-SCOPE.md](V1-SCOPE.md). Version 1 prioritizes simple reservations, mobile-first RTL UX, centralized administrator control and manually reconciled deposits.

### V1 scope being delivered
- [x] AJAX discovery API and UI: program/place/city/artist search; date, price, genre, city and venue-type filters; progressive loading; popularity sort; opt-in browser location and distance display when coordinates exist.
- [x] No oversized hero slider; homepage opens with a compact hero and discovery controls.
- [x] Event entry-price and artist metadata; cafe venue type and coordinates for discovery/map links.
- [x] Partner application form for cafes, restaurants and artists, centrally reviewed and status-tracked in the user account.
- [x] Cafe-owner operational dashboard removed from v1 setup and its operational REST permissions restricted to site administrators.
- [x] Manual 100 TRY bank-transfer/card-to-card deposit: customer reference submission, administrator verification after bank reconciliation, and booking confirmation blocked until deposit verification.
- [x] Three-hour customer cancellation window measured from booking creation; customer-cancelled deposit is non-refundable.
- [x] Configurable direct WhatsApp support link; this is click-to-chat, not an automated chatbot.
- [ ] Dedicated artist profile pages and user favorites/saved events.
- [ ] Fully reviewed, mobile/accessibility tested theme settings for colors, logo, typography and layout.
- [ ] Full end-to-end testing of deposit transfer/reference/rejection/retry/verification and customer notification flows.

### Explicitly deferred or removed
- Removed: large multi-image hero slider, social profiles/following, independent cafe-owner operations panel, automatic event publication without administrator approval.
- Phase next: ratings/reviews, internal wallet/credits, customer-selected exact tables and interactive seating maps, complex analytics, multi-currency and multilingual support.
- Future integration: online gateway and automated refunds, official WhatsApp Business API/webhooks and real chatbot require separate approval, credentials and test evidence.



## Phase 0 — foundation (completed)
- [x] Repository and WordPress plugin/theme separation
- [x] Plugin activation schema and REST route foundation
- [x] Booking code and controlled status transitions
- [x] RTL public theme shell
- [x] Architecture, scope and CI documentation

## Phase 1 — core booking
- [x] Cafe, event and table WordPress content types with administrator metadata forms
- [x] Hierarchical cafe-location taxonomy supports country, city, area and neighborhood terms.
- [x] Basic event headcount limits, selected-table overlap checks and per-cafe database locking (overlap and lock-contention integration tests pass; multi-process race remains)
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
- [x] Secure one-time check-in token, configurable expiry and append-only audit history (privacy/authorization are tested; full staging journey remains)
- [x] Event cancellation commission reversal policy snapshotted per booking
- [x] Manual settlement records and ledger CSV export

## Phase 3 — communications and identity
- [x] Optional Twilio SMS OTP adapter with expiry, resend limits, attempt throttling and WordPress login (live provider configuration/verification required)
- [x] Manual WhatsApp link helper and truthful action status; no delivery claim
- [x] Official WhatsApp Business API/webhook explicitly deferred outside v1 scope
- [x] Email notification queue, bounded retries, message history and duplicate prevention

## Phase 4 — release readiness
- [x] Initial PHPUnit settings and cafe-team authorization tests plus WordPress CI harness
- [x] Booking lifecycle/commission reversal, idempotency/customer isolation, overlapping-table rejection, selected REST privacy, OTP authentication/replay and abuse-control integration tests.
- [ ] Full REST permission/privacy matrix and true multi-process capacity-race test.
- [ ] Accessibility and responsive QA
- [x] Clean WordPress plugin/theme package extraction and schema activation smoke test.
- [ ] Historical schema migrations from every supported old version and uninstall/data-retention review.
- [x] Independent plugin/theme ZIP packaging workflow and extraction smoke test.
- [x] Deployment and operational runbook.
- [ ] Independent security review and staging acceptance.
