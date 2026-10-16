# Changelog

## [0.10.1] - 2026-10-16
- Serialize OTP requests per phone, supersede older challenges, and atomically enforce failed-attempt lockout.
- Add per-IP OTP throttling regression coverage and eliminate duplicate-key database noise in idempotent notification enqueue.
- Defer default page creation until WordPress `init` and remove duplicate cafe invitation shortcode registration.
- Improve keyboard skip navigation, visible form focus, form-control boundary contrast, reduced-motion support and OTP field labeling.
- Verify PHP/JavaScript source checks and WordPress integration tests on MySQL 8.0 and MariaDB 10.11 (17 tests, 75 assertions per engine before the final accessibility-only changes; latest CI is being re-run).

## [0.10.0] - 2026-10-16
- Add automated monthly cafe statement emails through the idempotent notification queue.
- Add a configurable 1–90 day due-date policy and overdue balance report by cafe/currency.
- Add administrator-managed cafe statement email metadata and a dedicated statements page.
- Extend source-contract checks to cover statements, recurrence, OTP and invitation workflows.
- Harden OTP resends with per-phone advisory locking, invalidation of superseded codes and atomic failed-attempt lockout updates.
- Expand REST privacy/authorization and OTP abuse regression tests; add serialized event-capacity invariant coverage.
- Repair CI test setup to use the maintained WP-CLI installer and avoid interactive recreation of the service database.
- Defer default page creation until WordPress `init` to avoid permalink-state errors during upgrades and automated bootstrap.
- Use `INSERT IGNORE` for notification deduplication to avoid expected duplicate-key database errors.


## [0.9.0] - 2026-10-16
- Add optional Twilio SMS OTP sign-in with hashed codes, five-minute expiry, resend throttling, per-phone/IP limits and five-attempt lockout.
- Add administrator-managed E.164 phone assignments to existing WordPress users; provider credentials must be configured outside source control.
- Add weekly/monthly recurrence controls that generate draft occurrences for editorial review, including featured image inheritance.
- Add event-cancellation commission reversal policy snapshot and schema version 13.
- Add WordPress settings tests and a WordPress test-suite CI workflow; run results are not yet verified.


## [0.8.0] - 2026-10-16
- Add expiring cafe-team invitation tokens with hash-only storage, email matching and single-use acceptance.
- Add an idempotent email notification queue with bounded exponential retries and delivery history.
- Add event-cancellation commission policy snapshots and schema version 12.
- Fix the check-in token REST route pattern.
- Add a WordPress PHPUnit test harness and CI workflow; execution results must be verified before release.


## [0.7.1] - 2026-10-16
- Add an administrator finance reconciliation summary by published cafe and currency.
- Separate accrued commissions, reversals, settlements, signed adjustments and net outstanding balance.
- Clarify the finance page's commission trigger text to match the configured default (`confirmed`) and optional `completed` trigger.
- Document that overdue balances cannot be inferred without a configured due-date policy.


## [0.7.0] - 2026-10-16
- Add booking-time snapshots for automatic commission reversal on cancellation and no-show.
- Append negative, idempotent commission reversal entries when the configured policy applies after accrual.
- Upgrade schema to version 11 for per-booking reversal policy snapshots.


## [0.6.0] - 2026-10-16
- Add one-time, random check-in tokens with configurable expiry and hash-only storage.
- Add customer token issuance and cafe-owner/admin check-in endpoints with atomic single-use consumption.
- Record token issuance and customer check-in events in the booking audit timeline.
- Add a customer-facing manual WhatsApp link when the cafe's contact number is valid, without claiming delivery.
- Add administrator-managed cafe team assignments for existing WordPress users with per-cafe REST authorization.
- Upgrade schema to version 10 and index token hashes.


## [0.5.0] - 2026-10-16
- Snapshot commission trigger status per booking; default accrual occurs on cafe confirmation, with completion available as an alternative.
- Upgrade database schema to version 9 for commission trigger snapshots.
- Add hierarchical cafe location terms and return root-to-leaf location paths from the public catalog.
- Add configurable customer cancellation cutoff and source-contract regression checks.
- Correct over-escaped booking REST routes and restrict cafe owner assignment to administrators.


## [0.4.1] - 2026-10-16
- Fix over-escaped booking history, completion, no-show, table assignment and cafe-cancellation REST route patterns.
- Restrict cafe owner assignment and manager-role grants to WordPress administrators.
- Add configurable customer cancellation cutoff and reject late/past-start customer cancellation attempts.
- Reset the booking idempotency key when the customer changes the request payload.
- Bound manual finance adjustment amounts to the supported ledger precision.
- Add a hierarchical cafe-location taxonomy for country/city/area/neighborhood terms.
- Add a configurable commission trigger, defaulting to cafe confirmation, and snapshot the policy for new bookings.
- Include the assigned root-to-leaf location path in the public catalog response.
- Add lightweight static source-contract checks; integration and concurrency tests remain outstanding.


## [0.4.0] - 2026-10-16
- Add an append-only commission ledger with commission amount/currency snapshotted at booking creation and accrual on completion.
- Add manual settlement records, cafe balance checks, append-only adjustments/reversals and ledger CSV export.
- Add administrator actions for completed/no-show bookings and cafe-owner completion/no-show actions.
- Require a table assignment before confirmation or completion when the cafe-assigned-table mode is active.
- Upgrade schema to version 5 with ledger and settlement tables.
- Upgrade schema to version 6 with append-only booking audit events and add an owner-scoped history API.
- Upgrade schema to version 7 and schedule a five-minute pending-reservation expiry worker with audit entries.
- Upgrade schema to version 8 with unique settlement request keys to prevent duplicate form submissions.
- Upgrade schema to version 9 to snapshot the configurable commission trigger status per booking; the default is confirmation.
- Lock the global currency setting after bookings or ledger entries exist to prevent mixed-currency balances.
- Add CSV formula-injection protection and test-plan coverage for finance and expiry workflows.


## [0.3.0] - 2026-10-16
- Add public cafe/event catalog and advisory availability REST APIs.
- Add a customer booking wizard, booking history and cancellation controls.
- Add administrator booking management and cafe-owner-scoped confirmation, rejection and cancellation.
- Add a cafe-manager role, ownership checks and a cafe dashboard page.
- Add manual table assignment with locked capacity/overlap revalidation.
- Add admin metadata forms for cafe contact/location/music, event schedule/capacity and table capacity.
- Add cafe/event archive and detail templates plus dynamic homepage sections.
- Create /book/, /my-account/ and /cafe-dashboard/ pages automatically when their slugs are unused.
- Add schema/page provisioning migration version 4 for upgraded installations.
- Validate configured event dates and operating hours, including overnight events.
- Add responsive styles for booking and listing interfaces.


## [0.2.0] - 2026-10-16
- Serialize booking creation per cafe using database advisory locks.
- Enforce configured event headcount limits and selected-table time-window conflicts.
- Expire stale pending booking holds when new requests are processed.
- Add a rejection endpoint with an optional reason, restricted to the assigned cafe owner or administrator.
- Add per-user idempotency keys with payload checks and duplicate-request recovery.
- Upgrade the booking schema to version 3 with a unique per-user idempotency index.
- Add a repeatable integration and concurrency test plan; execution remains outstanding.
- Parse reservation times in the WordPress site timezone and store UTC timestamps.
- Add configurable table-occupancy duration and a schema migration for the supporting index.
- Hide cafe owner identifiers from public REST metadata.
- Keep concurrency and integration testing marked as outstanding.

## [0.1.0] - 2026-10-16
- Establish WordPress plugin/theme project structure for MTN.
- Add versioned REST API foundation and booking persistence lifecycle.
- Add RTL theme templates and project architecture/roadmap documentation.
- Add GitHub Actions PHP syntax validation.
- Document integration and production-readiness gaps explicitly.
