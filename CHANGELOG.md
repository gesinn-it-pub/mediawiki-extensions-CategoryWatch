# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Changed

- **BREAKING**: Raised the minimum supported MediaWiki version from 1.35.3 to 1.39.
  Dropped the now-dead MW-1.35/1.36 compatibility fallbacks this required
  (`WatchedItemStore::getDefaultInstance()`, `UserOptionsLookup` `method_exists()`
  check, `WikiPage::newFromId()`/`getWikiPageFactory()` guard).
- CI matrix aligned with IDProvider: dropped the MW 1.35 leg, added an experimental
  MW 1.46/PHP 8.5 leg. Echo is now activated (`ECHO_VERSION`) on the 1.43 and 1.46
  legs — Echo has no `REL1_39` branch, so the 1.39 leg runs without it, same as
  before this change.
- Bumped `mediawiki/mediawiki-codesniffer` to 48.0.0 and `mediawiki/mediawiki-phan-config`
  to 0.16.0, matching IDProvider's versions (both compatible with the CI matrix's
  PHP 8.1 floor).

### Fixed

- `EchoEventPresentationModel::jsonSerialize()` didn't declare a return type, relying
  on `#[\ReturnTypeWillChange]` to suppress the resulting deprecation notice. Current
  Echo declares `jsonSerialize(): array` on the parent class, so the mismatched child
  signature is a PHP fatal error on MediaWiki versions bundling that Echo release.
  Cherry-picked from upstream
  ([f98325b](https://github.com/wikimedia/mediawiki-extensions-CategoryWatch/commit/f98325bce0062695e6122a24de84ca706d670647)).

### Added

- Phan static analysis, wired into CI as a dedicated step on the coverage matrix row.
  The optional Echo integration (`EchoEvent`, `EchoEventPresentationModel`) is not a
  hard `extension.json` dependency, so Echo is activated via `ECHO_VERSION` in the CI
  matrix and added to `.phan/config.php`'s `directory_list` (excluded from analysis),
  following the same pattern PageForms/SemanticResultFormats use for their dependency
  extensions — Phan resolves against Echo's real API instead of guessed stubs.

### Changed

- CI runner pinned to `ubuntu-24.04`, `actions/checkout` and `codecov/codecov-action`
  bumped, and the coverage matrix row's coverage output path aligned with the
  `build/coverage/php` convention used by the docker-compose-ci submodule.
- `.phpcs.xml` now excludes `vendor/`, `node_modules/`, `build/`, `coverage/`, and
  `.phan/` so build artifacts and analysis config are no longer scanned.

### Fixed

- `Hook::getWatchedItemStore()` kept a `WatchedItemStore::getDefaultInstance()`
  fallback that was already removed in this extension's minimum supported MediaWiki
  version (1.35.3); dropped the dead branch and typed the return as
  `WatchedItemStoreInterface`.
- `Hook::onCategoryAfterPageAdded()`/`onCategoryAfterPageRemoved()` called the
  deprecated `WikiPage::getRevision()`, which no longer exists on current MediaWiki;
  switched to `getRevisionRecord()`.
- `EchoEventPresentationModel::jsonSerialize()` called `Message::toString()` without
  its now-required `$format` argument, and carried a stale `@throws TimestampException`
  doc-block for an exception the method never throws.
- Doc-block of `$event` in `Hook::onEchoGetBundleRules()` declared a non-existent
  `Event` type instead of `EchoEvent`.

### Removed

- Packagist publishing metadata (`name`, `type`, `keywords`, `homepage`, `license`)
  from `composer.json`: `mediawiki/category-watch` was never actually published on
  Packagist, so the fields only implied an installation path that doesn't work.
  `composer.json` remains usable for local dev tooling (CI, linting, tests).

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
