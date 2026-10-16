# Build and release process

## Versioning
Use Semantic Versioning. Keep the plugin header and `MTN_CB_VERSION` aligned with the plugin release version. The theme has its own independent version and should be bumped only when theme files change; a combined release may package both independently versioned components. Current plugin version is 0.10.1; theme version is 0.2.1; database schema version is 13. Monthly cafe statement emails are queued idempotently and overdue balances are calculated using the configured month-end due-date policy. The code includes optional Twilio OTP sign-in and recurring event draft generation; SMS credentials and live delivery verification are still deployment requirements. Team invitations and the email notification queue are implemented, but live delivery and staging acceptance remain production gates. The finance admin page includes a reconciliation summary and the statements page queues monthly statements with due-date-aware overdue calculation; external bank-statement reconciliation remains manual. This code version has not been tagged or approved as a production release.

## CI
- `.github/workflows/php-lint.yml` runs PHP syntax validation, the booking-wizard JavaScript syntax check and static source-contract regression checks on pushes and pull requests.
- `.github/workflows/package.yml` validates JavaScript syntax, static source contracts and PHP syntax, then creates separate plugin/theme ZIP artifacts on manual run or a `v*` tag.
- `.github/workflows/wordpress-tests.yml` is configured to run PHPUnit against WordPress with MySQL 8 and MariaDB 10.11, load the packaged plugin/theme in a clean test core, cover schema, authorization, notification idempotency, OTP abuse controls and lock contention, and compare table row counts after a database backup/restore round trip. The latest patch passed on both engines with 17 tests and 75 assertions each. Staging, live-provider and production-host backup/restore gates remain.
- A successful ZIP build proves packaging and syntax only; it does not prove end-to-end functionality or production readiness.

## Release checklist
1. Run the test suite and inspect CI.
2. Test installation and upgrades on a clean supported WordPress instance.
3. Confirm plugin and theme ZIP contents each have the expected top-level directory.
4. Verify no credentials, customer data, debug dumps or local environment files are packaged.
5. Update CHANGELOG and release notes.
6. Tag the approved version (for example `v0.4.0`) and review the resulting artifacts.
7. Promote to production only after staging acceptance and backup/rollback verification.

## Repository visibility
The supplied repository is currently public. The project brief requests a private repository. Confirm and change repository visibility in GitHub settings before placing secrets, private customer data or sensitive operational documentation in this repository. Never commit secrets regardless of visibility.
