# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [4.0.0] - 2026-08-13

Raises the minimum supported MediaWiki version to 1.43 and removes the
now-unsupported global `Title`/`Category` class names, keeping the extension
working on current MediaWiki releases.

### Breaking Changes

- Raised the minimum supported MediaWiki version from 1.39 to 1.43. Dropped the
  now-dead MW-1.39/1.40 `ReadOnlyMode` compatibility fallback this required
  (`CategoryWatchNotifier` now type-hints `Wikimedia\Rdbms\ReadOnlyMode` directly
  instead of accepting either the old `\ReadOnlyMode` or the new namespaced class
  via a `method_exists()` check)
  [`b271549`](https://github.com/gesinn-it-pub/mediawiki-extensions-CategoryWatch/commit/b271549).
- Migrated `Title` and `Category` to their namespaced FQCNs
  (`MediaWiki\Title\Title`, `MediaWiki\Category\Category`) throughout `src/` and
  `tests/`. MediaWiki removed the global-namespace aliases for both between the
  1.43 and 1.44 branch cuts, so the bare `\Title`/`\Category` forms this
  extension used stop resolving on MW >= 1.44
  [`b5d6dbf`](https://github.com/gesinn-it-pub/mediawiki-extensions-CategoryWatch/commit/b5d6dbf).

### Changed

- Dropped the MW 1.39 CI matrix leg; 1.43 is now the floor/stable/coverage leg,
  with 1.46 as the experimental leg
  [`23c8f3a`](https://github.com/gesinn-it-pub/mediawiki-extensions-CategoryWatch/commit/23c8f3a).
- Bumped `mediawiki/mediawiki-codesniffer` to 51.0.1, `mediawiki/mediawiki-phan-config`
  to 0.20.0, and `mediawiki/minus-x` to 2.0.1
  [`51b83af`](https://github.com/gesinn-it-pub/mediawiki-extensions-CategoryWatch/commit/51b83af),
  and `eslint-config-wikimedia` to 0.32.5
  [`7f44260`](https://github.com/gesinn-it-pub/mediawiki-extensions-CategoryWatch/commit/7f44260).

### Fixed

- Excluded `coverage/` from the Gruntfile's `eslint:all` glob. The generated
  PHPUnit HTML coverage report vendors minified JS (`d3.min.js`, `jquery.min.js`,
  etc.) that was being linted whenever the directory existed locally
  [`38bb4c0`](https://github.com/gesinn-it-pub/mediawiki-extensions-CategoryWatch/commit/38bb4c0).
- `EchoEventPresentationModel` now stringifies `Title` objects
  (`getPrefixedText()`) before passing them to `Message::params()` instead of
  passing the object directly. This always worked at runtime via implicit
  `__toString()`, but the stricter type stubs in `mediawiki-phan-config` 0.20.0
  correctly flag it as a type mismatch. Also corrects
  `getRevisionEditSummary()`'s `@return` docblock from `string` to `Message`,
  matching what it actually returns
  [`4cd744f`](https://github.com/gesinn-it-pub/mediawiki-extensions-CategoryWatch/commit/4cd744f).

## [3.0.0] - 2026-08-13

Raises the minimum supported MediaWiki version to 1.39, extracts the notification
logic into an injectable service, and adds the extension's first PHPUnit test
coverage.

### Breaking Changes

- Raised the minimum supported MediaWiki version from 1.35.3 to 1.39. Dropped the
  now-dead MW-1.35/1.36 compatibility fallbacks this required
  (`WatchedItemStore::getDefaultInstance()`, `UserOptionsLookup` `method_exists()`
  check, `WikiPage::newFromId()`/`getWikiPageFactory()` guard)
  [`11eb98a`](https://github.com/gesinn-it-pub/mediawiki-extensions-CategoryWatch/commit/11eb98a),
  [`5222558`](https://github.com/gesinn-it-pub/mediawiki-extensions-CategoryWatch/commit/5222558).

### Added

- Unit test coverage for `Hook`, `CategoryWatchHooks`, `CategoryWatchNotifier`, and
  `EchoEventPresentationModel` — the extension previously shipped with zero PHPUnit
  tests despite having a `tests/phpunit/Unit` directory. Coverage on the MW 1.43 CI
  leg (Echo loaded) is now 100% lines on `Hook` and `CategoryWatchHooks`, 93% on
  `EchoEventPresentationModel`, and 61% on `CategoryWatchNotifier` — the remaining
  gaps are the `EchoEvent::create()` side-effect calls and the low-traffic
  bundled-notification branches, neither of which is meaningfully unit-testable
  [`71523c6`](https://github.com/gesinn-it-pub/mediawiki-extensions-CategoryWatch/commit/71523c6).
- Phan static analysis, wired into CI as a dedicated step on the coverage matrix row.
  The optional Echo integration (`EchoEvent`, `EchoEventPresentationModel`) is not a
  hard `extension.json` dependency, so Echo is activated via `ECHO_VERSION` in the CI
  matrix and added to `.phan/config.php`'s `directory_list` (excluded from analysis),
  following the same pattern PageForms/SemanticResultFormats use for their dependency
  extensions — Phan resolves against Echo's real API instead of guessed stubs
  [`abf8550`](https://github.com/gesinn-it-pub/mediawiki-extensions-CategoryWatch/commit/abf8550).

### Changed

- Bumped `grunt` from 1.6.1 to 1.6.3, resolving 7 high-severity Dependabot alerts
  (`js-yaml`, `lodash`, `minimatch`, `brace-expansion`, `picomatch`, `flatted`)
  carried transitively through `grunt`/`grunt-legacy-*`. `npm audit` now reports 0
  vulnerabilities
  [`177dae8`](https://github.com/gesinn-it-pub/mediawiki-extensions-CategoryWatch/commit/177dae8).
- Extracted the category-watcher/notification logic from the static `Hook` class into
  `CategoryWatchNotifier`, an injectable service registered via `src/ServiceWiring.php`.
  The `CategoryAfterPageAdded`, `CategoryAfterPageRemoved`, and `GetPreferences` hooks
  moved onto a new instance-based `CategoryWatchHooks` handler implementing MediaWiki's
  hook interfaces, wired through `extension.json`'s `HookHandlers`, mirroring the pattern
  used in the DisplayTitle extension. `Hook` now only holds the Echo-related static
  callbacks (`onEchoGetBundleRules`, `onBeforeCreateEchoEvent`, `userLocater`,
  `userFilter`), since Echo is an optional dependency whose hook interfaces don't exist
  when Echo isn't loaded
  [`2c1c6ac`](https://github.com/gesinn-it-pub/mediawiki-extensions-CategoryWatch/commit/2c1c6ac).
- CI matrix aligned with IDProvider: dropped the MW 1.35 leg, added an experimental
  MW 1.46/PHP 8.5 leg. Echo is now activated (`ECHO_VERSION`) on the 1.43 and 1.46
  legs — Echo has no `REL1_39` branch, so the 1.39 leg runs without it, same as
  before this change
  [`5222558`](https://github.com/gesinn-it-pub/mediawiki-extensions-CategoryWatch/commit/5222558).
- Bumped `mediawiki/mediawiki-codesniffer` to 48.0.0 and `mediawiki/mediawiki-phan-config`
  to 0.16.0, matching IDProvider's versions (both compatible with the CI matrix's
  PHP 8.1 floor)
  [`5222558`](https://github.com/gesinn-it-pub/mediawiki-extensions-CategoryWatch/commit/5222558).
- CI runner pinned to `ubuntu-24.04`, `actions/checkout` and `codecov/codecov-action`
  bumped, and the coverage matrix row's coverage output path aligned with the
  `build/coverage/php` convention used by the docker-compose-ci submodule
  [`abf8550`](https://github.com/gesinn-it-pub/mediawiki-extensions-CategoryWatch/commit/abf8550).
- `.phpcs.xml` now excludes `vendor/`, `node_modules/`, `build/`, `coverage/`, and
  `.phan/` so build artifacts and analysis config are no longer scanned
  [`68e8477`](https://github.com/gesinn-it-pub/mediawiki-extensions-CategoryWatch/commit/68e8477).

### Fixed

- `eslint:all` in `Gruntfile.js` only excluded `node_modules/` and `vendor/`, so a
  locally generated `build/coverage/php/_js/*.min.js` (bundled jQuery/Bootstrap/D3
  from the HTML coverage report) got linted as extension source, producing ~175k
  bogus errors and aborting `grunt test`. Excluded `build/` from the glob
  [`50271ea`](https://github.com/gesinn-it-pub/mediawiki-extensions-CategoryWatch/commit/50271ea).
- `phpunit-coverage`'s `--coverage-*` flags were appended after `@phpunit`'s closing
  `bash -c '...'` quote instead of being passed into it, so PHPUnit ran without coverage
  instrumentation and no `coverage.xml`/HTML report was ever generated despite tests
  passing
  [`3170c22`](https://github.com/gesinn-it-pub/mediawiki-extensions-CategoryWatch/commit/3170c22).
- `composer phpunit`/`phpunit-coverage` called the deprecated
  `tests/phpunit/phpunit.php` maintenance-script entry point, which MediaWiki 1.46
  removes outright ("Could not open input file"). Migrated to `vendor/bin/phpunit`
  with `generatePHPUnitConfig.php`, per the pattern IDProvider already uses
  [`cb52f68`](https://github.com/gesinn-it-pub/mediawiki-extensions-CategoryWatch/commit/cb52f68).
- `EchoEventPresentationModel::jsonSerialize()` didn't declare a return type, relying
  on `#[\ReturnTypeWillChange]` to suppress the resulting deprecation notice. Current
  Echo declares `jsonSerialize(): array` on the parent class, so the mismatched child
  signature is a PHP fatal error on MediaWiki versions bundling that Echo release.
  Cherry-picked from upstream
  ([f98325b](https://github.com/wikimedia/mediawiki-extensions-CategoryWatch/commit/f98325bce0062695e6122a24de84ca706d670647))
  [`c136af6`](https://github.com/gesinn-it-pub/mediawiki-extensions-CategoryWatch/commit/c136af6).
- `Hook::getWatchedItemStore()` kept a `WatchedItemStore::getDefaultInstance()`
  fallback that was already removed in this extension's minimum supported MediaWiki
  version (1.35.3); dropped the dead branch and typed the return as
  `WatchedItemStoreInterface`
  [`3bfb361`](https://github.com/gesinn-it-pub/mediawiki-extensions-CategoryWatch/commit/3bfb361).
- `Hook::onCategoryAfterPageAdded()`/`onCategoryAfterPageRemoved()` called the
  deprecated `WikiPage::getRevision()`, which no longer exists on current MediaWiki;
  switched to `getRevisionRecord()`
  [`3bfb361`](https://github.com/gesinn-it-pub/mediawiki-extensions-CategoryWatch/commit/3bfb361).
- `EchoEventPresentationModel::jsonSerialize()` called `Message::toString()` without
  its now-required `$format` argument, and carried a stale `@throws TimestampException`
  doc-block for an exception the method never throws
  [`3bfb361`](https://github.com/gesinn-it-pub/mediawiki-extensions-CategoryWatch/commit/3bfb361).
- Doc-block of `$event` in `Hook::onEchoGetBundleRules()` declared a non-existent
  `Event` type instead of `EchoEvent`
  [`3bfb361`](https://github.com/gesinn-it-pub/mediawiki-extensions-CategoryWatch/commit/3bfb361).

### Removed

- Packagist publishing metadata (`name`, `type`, `keywords`, `homepage`, `license`)
  from `composer.json`: `mediawiki/category-watch` was never actually published on
  Packagist, so the fields only implied an installation path that doesn't work.
  `composer.json` remains usable for local dev tooling (CI, linting, tests)
  [`108bb5a`](https://github.com/gesinn-it-pub/mediawiki-extensions-CategoryWatch/commit/108bb5a).

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

[Unreleased]: https://github.com/gesinn-it-pub/mediawiki-extensions-CategoryWatch/compare/4.0.0...HEAD
[4.0.0]: https://github.com/gesinn-it-pub/mediawiki-extensions-CategoryWatch/compare/3.0.0...4.0.0
[3.0.0]: https://github.com/gesinn-it-pub/mediawiki-extensions-CategoryWatch/compare/2.0.1...3.0.0
[2.0.1]: https://github.com/gesinn-it-pub/mediawiki-extensions-CategoryWatch/releases/tag/2.0.1
