# Changelog

## [0.2.0] - 2026-10-16
- Serialize booking creation per cafe using database advisory locks.
- Enforce configured event headcount limits and selected-table time-window conflicts.
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
