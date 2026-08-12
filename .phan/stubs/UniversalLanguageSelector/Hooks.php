<?php

namespace UniversalLanguageSelector;

use MediaWiki\Config\Config;
use MediaWiki\Skin\Skin;

class Hooks {
	/**
	 * @param Skin $skin
	 * @param Config $config
	 * @return bool
	 */
	public static function isLanguageSelectorV2Enabled( Skin $skin, Config $config ) {
		return false;
	}
}
