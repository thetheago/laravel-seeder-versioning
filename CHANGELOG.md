# Changelog

All notable changes to this project are documented here. Format based on [Keep a Changelog](https://keepachangelog.com/); versions follow [SemVer](https://semver.org/).

## [Unreleased]

### Added

- `LICENSE`, `CONTRIBUTING.md`, `CODE_OF_CONDUCT.md`, issue/PR templates and CI.
- `except` config option to skip seeders, with `DatabaseSeeder` excluded by default (#11).

### Fixed

- `DatabaseSeeder` is no longer versioned and run, so the seeders it calls no longer run twice (#3).
