<?php

namespace WikimediaBadges\Tests;

use PHPUnit_Framework_TestCase;
use SkinTemplate;
use WikimediaBadges\BeforePageDisplayHookHandler;

/**
 * @covers WikimediaBadges\BeforePageDisplayHookHandler
 *
 * @group WikimediaBadges
 *
 * @license GNU GPL v2+
 * @author Marius Hoch < hoo@online.de >
 */
class BeforePageDisplayHookHandlerTest extends PHPUnit_Framework_TestCase {

	public function testOnBeforePageDisplay() {
		$skin = new SkinTemplate();
		$out = $this->getMockBuilder( 'OutputPage' )
			->disableOriginalConstructor()
			->getMock();
		$out->expects( $this->once() )
			->method( 'addModuleStyles' )
			->with( 'ext.wikimediaBadges' );

		$this->assertTrue( BeforePageDisplayHookHandler::onBeforePageDisplay( $out, $skin ) );
	}

}
