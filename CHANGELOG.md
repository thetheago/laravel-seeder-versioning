# Changelog

All notable changes to this project are documented here. Format based on [Keep a Changelog](https://keepachangelog.com/); versions follow [SemVer](https://semver.org/).

## [Unreleased]

### Added

- `LICENSE`, `CONTRIBUTING.md`, `CODE_OF_CONDUCT.md`, issue/PR templates and CI.
- `except` config option to skip seeders, with `DatabaseSeeder` excluded by default (#11).
- `--force` option for `seed:migrate`.
- Execution history: each registered seeder adds a row to `seeder_version_history` (configurable with `history_table`) with the hash, previous hash, hash-only flag and timestamp. The table is created automatically on the next `seed:migrate` (#17).
- `seed:status` command: shows the last seeder run and the new or modified seeders, without running them (#13).

### Fixed

- `ensureSetup()` checks the configured `table` name. With a custom name, `seed:migrate` tried to create the table again on every run and failed with "table already exists" (#5).
- `DatabaseSeeder` is no longer versioned and run, so the seeders it calls no longer run twice (#3).
- `seed:migrate` asks for confirmation in production, like `db:seed` (#6). Deploys that run it in production without a terminal must now pass `--force`, otherwise the command is cancelled and exits with `1`.
