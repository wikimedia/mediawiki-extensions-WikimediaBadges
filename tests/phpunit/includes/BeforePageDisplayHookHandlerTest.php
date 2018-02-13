<?php

namespace WikimediaBadges\Tests;

use OutputPage;
use PHPUnit_Framework_TestCase;
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
class BeforePageDisplayHookHandlerTest extends PHPUnit_Framework_TestCase {

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
