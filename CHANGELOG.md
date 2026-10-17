# Changelog

All notable changes to this project are documented here. Format based on [Keep a Changelog](https://keepachangelog.com/); versions follow [SemVer](https://semver.org/).

## [Unreleased]

### Added

- `LICENSE`, `CONTRIBUTING.md`, `CODE_OF_CONDUCT.md`, issue/PR templates and CI.
- `except` config option to skip seeders, with `DatabaseSeeder` excluded by default (#11).
- `--force` option for `seed:migrate`.
- Execution history: each registered seeder adds a row to `seeder_version_history` (configurable with `history_table`) with the hash, previous hash, hash-only flag and timestamp. The table is created automatically on the next `seed:migrate` (#17).
- `seed:status` command: shows the last seeder run and the new or modified seeders, without running them (#13).
- Preview before running: `seed:migrate` shows a table with each pending seeder, the lines changed since its last run and the risks found by static analysis (`truncate()`, `delete()`, `insert()` without upsert...), and asks for confirmation. `seed:migrate --pretend` shows only the table. `seed:status` shows the same columns (#20).
- The seeder content is stored in a new `content` column of the versions table, to count changed lines. It is added automatically on the next `seed:migrate`.

### Changed

- `seed:migrate` asks for confirmation in a terminal in every environment, not only in production. Without a terminal it still runs, except in production. Use `--force` to never ask (#20).
- Short hashes in `seed:status` have 6 characters instead of 12 (#20).

### Fixed

- `DatabaseSeeder` is no longer versioned and run, so the seeders it calls no longer run twice (#3).
- `seed:migrate` asks for confirmation in production, like `db:seed` (#6). Deploys that run it in production without a terminal must now pass `--force`, otherwise the command is cancelled and exits with `1`.
