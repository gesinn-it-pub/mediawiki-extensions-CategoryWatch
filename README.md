# CategoryWatch

[![CI](https://github.com/gesinn-it-pub/mediawiki-extensions-CategoryWatch/actions/workflows/ci.yml/badge.svg)](https://github.com/gesinn-it-pub/mediawiki-extensions-CategoryWatch/actions/workflows/ci.yml)
[![codecov](https://codecov.io/gh/gesinn-it-pub/mediawiki-extensions-CategoryWatch/branch/master/graph/badge.svg)](https://app.codecov.io/gh/gesinn-it-pub/mediawiki-extensions-CategoryWatch)

Main development Git repository on Gerrit: https://gerrit.wikimedia.org/r/#/admin/projects/mediawiki/extensions/CategoryWatch

Here is the Phabricator Diffusion: https://phabricator.wikimedia.org/diffusion/ECWA/

Mirror on GitHub: https://github.com/seanchen/CategoryWatch

MediaWiki extension CategoryWatch, https://www.mediawiki.org/wiki/Extension:CategoryWatch

Initial commit is forked from commit 4ad0f63:
https://github.com/OrganicDesign/extensions/tree/4ad0f631438ed16c05edfa08e65e0de00b4b1342/MediaWiki-Legacy/CategoryWatch

## Supported versions

| Version | Branch   | MediaWiki | Status                               |
|---------|----------|-----------|--------------------------------------|
| 4.x     | `master` | >= 1.43   | Current                              |
| 3.x     | `3.x`    | >= 1.39   | Maintained, receives bug fixes       |
| 2.x     | `2.x`    | >= 1.39   | End of life, no further fixes        |

## How to debug

3 simple steps to debug an extension.

* using function wfDebugLog to log message,
  using the extension name as the group. for example:
```php
wfDebugLog('CategoryWatch', 'loading extension...');
```
* enable debug log for the group, bascially the extension name.
  set the log file name.
```php
# in file LocalSettings.php
$wgDebugLogGroups['CategoryWatch'] = '/path/to/log/mw-categorywatch.log';
```
* tail the log file to debug...
