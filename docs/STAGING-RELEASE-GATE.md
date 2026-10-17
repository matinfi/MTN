# Staging and production release gate

This document is an evidence checklist, not a claim that a staging review has occurred. Keep the release blocked until a named reviewer records actual results from the intended hosting stack.

## 1. Environment record

- Staging URL:
- WordPress version:
- PHP version:
- Database engine/version:
- Browser/device matrix:
- Test date (UTC):
- Reviewer:
- Release candidate commit:
- Backup artifact/reference:
- Rollback owner:

## 2. Accessibility and responsive review

Test the actual rendered site, not only source files.

- [ ] Complete booking, account, OTP and cafe-dashboard flows using keyboard only.
- [ ] Verify visible focus, skip-link behavior, logical focus order and no keyboard traps.
- [ ] Confirm every form control has an accessible name and validation errors/status updates are announced by assistive technology.
- [ ] Check text and controls for WCAG 2.2 AA contrast; do not rely on color alone to communicate state.
- [ ] Test at 320, 375, 768, 1024 and 1440 CSS-pixel viewport widths; no unintended horizontal page overflow.
- [ ] Test zoom at 200% and reflow at 400% where supported.
- [ ] Test at least one current Chromium-based browser, Firefox, Safari/iOS, Android Chrome, and one screen reader (NVDA or VoiceOver).
- [ ] Confirm reduced-motion preferences are respected and all controls have usable touch targets.
- [ ] Record findings, severity, reproduction steps, and evidence; re-test every fix.

## 3. Security and privacy review

- [ ] Confirm HTTPS, secure cookie behavior, WordPress nonce handling and authenticated REST requests.
- [ ] Verify anonymous, unrelated customer, unassigned cafe manager, removed team member, assigned manager and administrator access boundaries.
- [ ] Verify booking IDs, customer notes, event history, check-in token and finance exports do not leak across accounts.
- [ ] Exercise OTP rate limits, expiry, resend invalidation, five-attempt lockout, one-time consumption and real provider delivery with non-production credentials.
- [ ] Confirm Twilio secrets are injected through protected configuration and never appear in repository history, logs, page output or error responses.
- [ ] Verify invitation expiry, email matching, single-use acceptance and revocation after team removal.
- [ ] Test CSV formula-injection payloads, capability checks, CSRF protection and unauthorized finance adjustments.
- [ ] Review plugin/theme dependencies, server logs and security headers on the intended host.

## 4. Booking, finance and recovery

- [ ] Run all scenarios in [TESTING.md](TESTING.md), including duplicate requests, overlapping tables, final-seat competition, cancellation cutoff and stale pending expiry.
- [ ] Run competing booking requests as separate processes against the actual MySQL/MariaDB engine; record request inputs, resulting rows and statuses.
- [ ] Rehearse upgrade from schema versions 1–12 on disposable copies, then verify data and ledger invariants.
- [ ] Create a production-like database/files backup and restore it into an isolated environment; verify row counts and key booking/ledger balances.
- [ ] Test the rollback procedure and confirm no irreversible financial action occurs during rollback.
- [ ] Verify email delivery, queued retry behavior, monthly statements, due dates and overdue balances with representative data.
- [ ] Verify the site's real cron reliably runs expiry, notification, recurrence and statement jobs.

## 5. Performance checks

- [ ] Test catalog, availability, booking creation, customer booking list, cafe dashboard and finance pages with representative data volumes.
- [ ] Capture p50/p95 response time, error rate, PHP/database resource use and query counts; record the tested dataset and environment.
- [ ] Check query plans and indexes for booking conflicts, capacity checks, ledger balance and statement generation.
- [ ] Confirm caching excludes authenticated booking/account/dashboard responses and does not cache personalized REST responses.
- [ ] Set deployment-specific acceptance thresholds before the run and attach results.

## 6. Final sign-off

- [ ] All critical/high findings fixed or explicitly accepted by the accountable owner.
- [ ] All automated CI checks green for the exact release candidate commit.
- [ ] Accessibility, responsive, security and performance evidence attached.
- [ ] Staging finance, notification and backup/restore evidence reviewed.
- [ ] Repository visibility and collaborator access match the intended confidentiality policy.
- [ ] Production approval recorded by the release owner.

| Sign-off | Name | Date (UTC) | Evidence / ticket |
| --- | --- | --- | --- |
| Engineering | | | |
| QA / accessibility | | | |
| Security | | | |
| Finance / operations | | | |
| Release owner | | | |

**Release rule:** do not mark this gate complete from static source review or CI alone. Live provider delivery, supported-device behavior, hosting-specific security/performance and staging sign-off require evidence from the actual staging environment.
