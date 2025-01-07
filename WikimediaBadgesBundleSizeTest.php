<?php

namespace Tests\WikimediaBadges\Structure;

/**
 * @group WikimediaBadges
 */
class WikimediaBadgesBundleSizeTest extends \MediaWiki\Tests\Structure\BundleSizeTestBase {

	/** @inheritDoc */
	public function getBundleSizeConfig(): string {
		return dirname( __DIR__, 3 ) . '/bundlesize.config.json';
	}
}
