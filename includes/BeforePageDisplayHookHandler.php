<?php

namespace WikimediaBadges;

use OutputPage;
use Skin;

/**
 * Handler for the BeforePageDisplay hook.
 *
 * @since 0.1
 *
 * @license GNU GPL v2+
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class BeforePageDisplayHookHandler {

	/**
	 * Handler for the BeforePageDisplay hook
	 *
	 * @since 0.1
	 *
	 * @param OutputPage $out
	 * @param Skin $skin
	 *
	 * @return bool
	 */
	public static function onBeforePageDisplay( OutputPage &$out, Skin &$skin ) {
		$out->addModuleStyles( 'ext.wikimediaBadges' );
		return true;
	}

}
