<?php

namespace WikimediaBadges\Tests;

use MediaWiki\Config\Config;
use MediaWiki\Output\OutputPage;
use MediaWiki\Skin\SkinTemplate;
use MediaWiki\User\User;
use MediaWikiIntegrationTestCase;
use WikimediaBadges\BeforePageDisplayHookHandler;

/**
 * @covers WikimediaBadges\BeforePageDisplayHookHandler
 *
 * @group WikimediaBadges
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch < hoo@online.de >
 */
class BeforePageDisplayHookHandlerTest extends MediaWikiIntegrationTestCase {
	public function testOnBeforePageDisplay() {
		$skin = new SkinTemplate();
		$out = $this->getMockBuilder( OutputPage::class )
			->disableOriginalConstructor()
			->getMock();

		$out->method( 'getUser' )
			->willReturn( $this->createMock( User::class ) );
		$out->method( 'getConfig' )
			->willReturn( $this->createMock( Config::class ) );

		$out->expects( $this->once() )
			->method( 'getProperty' )
			->with( 'wikibase_badges' )
			->willReturn( [ 'enwiki' =>
				[
					'class' => 'badge-Q18349139',
					'label' => 'good-article',
				] ] );
		$out->expects( $this->once() )
			->method( 'addModuleStyles' )
			->with( 'ext.wikimediaBadges' );

		( new BeforePageDisplayHookHandler )->onBeforePageDisplay( $out, $skin );
	}

	public function testOnBeforePageDisplayEmpty() {
		$skin = new SkinTemplate();
		$out = $this->getMockBuilder( OutputPage::class )
			->disableOriginalConstructor()
			->getMock();

		$out->method( 'getUser' )
			->willReturn( $this->createMock( User::class ) );
		$out->method( 'getConfig' )
			->willReturn( $this->createMock( Config::class ) );

		$out->expects( $this->once() )
			->method( 'getProperty' )
			->with( 'wikibase_badges' )
			->willReturn( null );
		$out->expects( $this->never() )
			->method( 'addModuleStyles' );
		( new BeforePageDisplayHookHandler )->onBeforePageDisplay( $out, $skin );
	}

}
