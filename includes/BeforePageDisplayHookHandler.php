<?php

namespace WikimediaBadges;

use MediaWiki\Output\Hook\BeforePageDisplayHook;

/**
 * Handler for the BeforePageDisplay hook.
 *
 * @since 0.1
 *
 * @license GPL-2.0-or-later
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class BeforePageDisplayHookHandler implements BeforePageDisplayHook {

	/** @inheritDoc */
	public function onBeforePageDisplay( $out, $skin ): void {
		if ( $out->getProperty( 'wikibase_badges' ) ) {
			$out->addModuleStyles( 'ext.wikimediaBadges' );
		}
	}

}
