# Build and release process

## Versioning
Use Semantic Versioning. Keep the plugin header, theme header, constants, changelog and release tag aligned. Current plugin version is 0.4.1; database schema version is 9. Version 0.4.1 is a patch-level code update and has not been tagged or approved as a production release.

## CI
- `.github/workflows/php-lint.yml` runs PHP syntax validation on pushes and pull requests.
- `.github/workflows/package.yml` validates PHP and creates separate plugin/theme ZIP artifacts on manual run or a `v*` tag.
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
