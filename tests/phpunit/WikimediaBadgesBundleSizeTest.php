<?php

namespace WikimediaBadges\Tests;

/**
 * @group WikimediaBadges
 */
class WikimediaBadgesBundleSizeTest extends \MediaWiki\Tests\Structure\BundleSizeTestBase {

	/** @inheritDoc */
	public function getBundleSizeConfig(): string {
		return dirname( __DIR__, 2 ) . '/bundlesize.config.json';
	}
}
