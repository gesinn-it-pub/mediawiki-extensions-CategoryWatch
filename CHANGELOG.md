# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Changed

- CI runner pinned to `ubuntu-24.04`, `actions/checkout` and `codecov/codecov-action`
  bumped, and the coverage matrix row's coverage output path aligned with the
  `build/coverage/php` convention used by the docker-compose-ci submodule.
- `.phpcs.xml` now excludes `vendor/`, `node_modules/`, `build/`, and `coverage/` so
  build artifacts are no longer scanned.

## [2.0.1] - 2026-08-12

### Changed

- Raised the minimum supported MediaWiki version to 1.35.3 and replaced deprecated
  APIs used up to that point: `wfGetDB`, `User::newFromId` (now `UserFactory`), and
  `DB_MASTER` (now `DB_PRIMARY`).
- Updated `mediawiki/mediawiki-codesniffer` to 47.0.0 and refreshed other build/CI
  dependencies.
- CI now covers MediaWiki 1.35, 1.39, and 1.43.

### Added

- `tests/phpunit/Unit` directory scaffolded for future PHPUnit coverage.

[Unreleased]: https://github.com/gesinn-it-pub/mediawiki-extensions-CategoryWatch/compare/2.0.1...HEAD
[2.0.1]: https://github.com/gesinn-it-pub/mediawiki-extensions-CategoryWatch/releases/tag/2.0.1
