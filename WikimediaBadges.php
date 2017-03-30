<?php

/**
 * Extension which contains different themes
 * to display badges on Wikimedia projects
 */

/**
 * Entry point for for the WikimediaBadges extension.
 *
 * @see README.md
 * @see https://github.com/wmde/WikimediaBadges
 * @license GNU GPL v2+
 */

if ( function_exists( 'wfLoadExtension' ) ) {
	wfLoadExtension( 'WikimediaBadges', __DIR__ . '/extension.json' );
	// Keep i18n globals so mergeMessageFileList.php doesn't break
	$wgMessagesDirs['WikimediaBadges'] = __DIR__ . '/i18n';
	/*wfWarn(
		'Deprecated PHP entry point used for WikimediaBadges extension. Please use wfLoadExtension instead, ' .
		'see https://www.mediawiki.org/wiki/Extension_registration for more details.'
	);*/
	return;
} else {
	die( 'This version of the WikimediaBadges extension requires MediaWiki 1.25+' );
}