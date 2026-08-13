<?php

$cfg = require __DIR__ . '/../vendor/mediawiki/mediawiki-phan-config/src/config.php';

$cfg['directory_list'] = array_merge(
	$cfg['directory_list'],
	[
		'src',
	]
);

// Make dependency extensions visible to Phan's type-checker: CategoryWatch
// integrates with Echo (EchoEvent, EchoEventPresentationModel) but doesn't
// declare it as a hard extension.json dependency, so it's only present when
// activated in the CI matrix (see ECHO_VERSION in ci.yml / build/Dockerfile).
// mediawiki-phan-config only adds MW core and MW vendor to directory_list by
// default, never extensions/. Without this, every call into Echo's classes
// surfaces as PhanUndeclaredClass / PhanUndeclaredMethod noise instead of
// being checked against Echo's actual API.
$IP = getenv( 'MW_INSTALL_PATH' ) !== false
	? str_replace( '\\', '/', getenv( 'MW_INSTALL_PATH' ) )
	: '../..';

$cfg['directory_list'][] = $IP . '/extensions/Echo';
$cfg['exclude_analysis_directory_list'][] = $IP . '/extensions/Echo';

return $cfg;
