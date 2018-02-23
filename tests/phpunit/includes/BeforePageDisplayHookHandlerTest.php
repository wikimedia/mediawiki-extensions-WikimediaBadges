<?php

namespace WikimediaBadges\Tests;

use OutputPage;
use PHPUnit\Framework\TestCase;
use SkinTemplate;
use WikimediaBadges\BeforePageDisplayHookHandler;

/**
 * @covers WikimediaBadges\BeforePageDisplayHookHandler
 *
 * @group WikimediaBadges
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch < hoo@online.de >
 */
class BeforePageDisplayHookHandlerTest extends TestCase {

	public function testOnBeforePageDisplay() {
		$skin = new SkinTemplate();
		$out = $this->getMockBuilder( OutputPage::class )
			->disableOriginalConstructor()
			->getMock();
		$out->expects( $this->once() )
			->method( 'addModuleStyles' )
			->with( 'ext.wikimediaBadges' );

		BeforePageDisplayHookHandler::onBeforePageDisplay( $out, $skin );
	}

}
