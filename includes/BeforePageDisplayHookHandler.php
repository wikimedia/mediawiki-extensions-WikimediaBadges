<?php

namespace WikimediaBadges;

use OutputPage;
use Skin;

/**
 * Handler for the BeforePageDisplay hook.
 *
 * @since 0.1
 *
 * @license GPL-2.0-or-later
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class BeforePageDisplayHookHandler {

	/**
	 * Handler for the BeforePageDisplay hook
	 *
	 * @param OutputPage $out
	 * @param Skin $skin
	 */
	public static function onBeforePageDisplay( OutputPage $out, Skin $skin ) {
		$out->addModuleStyles( 'ext.wikimediaBadges' );
	}

}
