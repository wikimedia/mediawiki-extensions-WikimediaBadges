<?php

/**
 * WikimediaBadges ResourceLoader modules
 *
 * @since 0.1
 *
 * @licence GNU GPL v2+
 * @author Bene* < benestar.wikimedia@gmail.com >
 */

return call_user_func( function() {
	$remoteExtPathParts = explode( DIRECTORY_SEPARATOR . 'extensions' . DIRECTORY_SEPARATOR , __DIR__, 2 );
	$moduleTemplate = array(
		'localBasePath' => __DIR__,
		'remoteExtPath' => $remoteExtPathParts[1]
	);

	$modules = array(
		'ext.wikimediaBadges' => $moduleTemplate + array(
			'position' => 'bottom',
			'skinStyles' => array(
				'vector' => 'skins/vector/wikimedia-badges.css',
				'monobook' => 'skins/monobook/wikimedia-badges.css',
				'cologneblue' => 'skins/cologneblue/wikimedia-badges.css',
				'modern' => 'skins/modern/wikimedia-badges.css',
			)
		)
	);

	return $modules;
} );
