# Changelog

All notable changes to this project are documented here. Format based on [Keep a Changelog](https://keepachangelog.com/); versions follow [SemVer](https://semver.org/).

## [Unreleased]

### Added

- `LICENSE`, `CONTRIBUTING.md`, `CODE_OF_CONDUCT.md`, issue/PR templates and CI.
- Execution history: each registered seeder adds a row to `seeder_version_history` (configurable with `history_table`) with the hash, previous hash, hash-only flag and timestamp. The table is created automatically on the next `seed:migrate`.
