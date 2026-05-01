<?php

namespace WikimediaBadges;

use MediaWiki\Output\Hook\BeforePageDisplayHook;
use MediaWiki\Registration\ExtensionRegistry;
use UniversalLanguageSelector\Hooks;

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
			// Always load the base styles: the v2 styles only apply once the
			// JS-based ULS rewrite mounts, so this is the no-JS fallback.
			$out->addModuleStyles( 'ext.wikimediaBadges' );

			if (
				ExtensionRegistry::getInstance()->isLoaded( 'UniversalLanguageSelector' ) &&
				Hooks::isLanguageSelectorV2Enabled( $out->getUser(), $skin, $out->getConfig() )
			) {
				$out->addModuleStyles( 'ext.wikimediaBadges.ulsV2' );
			}
		}
	}

}
