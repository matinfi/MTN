# Changelog

## [0.2.0] - 2026-10-16
- Serialize booking creation per cafe using database advisory locks.
- Enforce configured event headcount limits and selected-table time-window conflicts.
- Expire stale pending booking holds when new requests are processed.
- Add an administrator-only rejection endpoint with an optional reason.
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
