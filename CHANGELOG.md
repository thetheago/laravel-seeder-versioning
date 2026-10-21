# Changelog

All notable changes to this project are documented here. Format based on [Keep a Changelog](https://keepachangelog.com/); versions follow [SemVer](https://semver.org/).

## [Unreleased]

### Added

- `LICENSE`, `CONTRIBUTING.md`, `CODE_OF_CONDUCT.md`, issue/PR templates and CI.
- `except` config option to skip seeders, with `DatabaseSeeder` excluded by default (#11).
- `--force` option for `seed:migrate`.
- Execution history: each registered seeder adds a row to `seeder_version_history` (configurable with `history_table`) with the hash, previous hash, hash-only flag and timestamp. The table is created automatically on the next `seed:migrate` (#17).
- `seed:status` command: shows the last seeder run and the new or modified seeders, without running them (#13).
- Preview before running: `seed:migrate` shows a table with each pending seeder and the risks found by static analysis of its code (`truncate()`, `delete()`, `insert()` without upsert...), and asks for confirmation. `seed:migrate --pretend` shows only the table. `seed:status` shows the same columns (#20).

### Changed

- `seed:migrate` asks for confirmation in a terminal in every environment, not only in production. Without a terminal it still runs, except in production. Use `--force` to never ask (#20).
- Short hashes in `seed:status` have 6 characters instead of 12 (#20).

### Fixed

- A failing seeder no longer leaves partial data or aborts without a report: each seeder runs in a transaction with its hash registration, a failure rolls back that seeder, keeps it unregistered so it runs again next time, and `seed:migrate` reports which seeder failed and exits with `1` (#8).
- `ensureSetup()` checks the configured `table` name. With a custom name, `seed:migrate` tried to create the table again on every run and failed with "table already exists" (#5).
- `DatabaseSeeder` is no longer versioned and run, so the seeders it calls no longer run twice (#3).
- `seed:migrate` asks for confirmation in production, like `db:seed` (#6). Deploys that run it in production without a terminal must now pass `--force`, otherwise the command is cancelled and exits with `1`.
