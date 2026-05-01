<?php

namespace UniversalLanguageSelector;

use MediaWiki\Config\Config;
use MediaWiki\Skin\Skin;
use MediaWiki\User\User;

class Hooks {
	/**
	 * @param User $user
	 * @param Skin $skin
	 * @param Config $config
	 * @return bool
	 */
	public static function isLanguageSelectorV2Enabled( User $user, Skin $skin, Config $config ) {
		return false;
	}
}
