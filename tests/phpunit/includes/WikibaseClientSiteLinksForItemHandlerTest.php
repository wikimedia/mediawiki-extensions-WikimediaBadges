<?php

namespace WikimediaBadges\Tests;

use DataValues\DecimalValue;
use DataValues\StringValue;
use MediaWikiTestCase;
use Wikibase\Client\Usage\UsageAccumulator;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\SiteLink;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikimedia\Assert\ParameterTypeException;
use WikimediaBadges\WikibaseClientSiteLinksForItemHandler;

/**
 * @covers \WikimediaBadges\WikibaseClientSiteLinksForItemHandler
 *
 * @group WikimediaBadges
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch < hoo@online.de >
 */
class WikibaseClientSiteLinksForItemHandlerTest extends MediaWikiTestCase {

	/**
	 * @dataProvider doAddToSidebarProvider
	 */
	public function testDoAddToSidebar(
		array $expected,
		array $sidebar,
		Item $item
	) {
		$handler = new WikibaseClientSiteLinksForItemHandler( 'P373' );
		$handler->doProvideSiteLinks( $item, $sidebar );
		$this->assertEquals( $expected, $sidebar );
	}

	public function doAddToSidebarProvider() {
		$wikiquoteLink = new SiteLink( 'enwikiquote', 'Ams' );
		$oldCommonsLink = new SiteLink( 'commonswiki', 'Amsterdam' );
		$newCommonsLink = new SiteLink( 'commonswiki', 'Category:Amsterdam' );

		return [
			'Item without commons category statement' => [
				[],
				[],
				new Item( new ItemId( 'Q42' ) )
			],
			'Sidebar without commons link gets amended' => [
				[
					'enwikiquote' => $wikiquoteLink,
					'commonswiki' => $newCommonsLink
				],
				[
					'enwikiquote' => $wikiquoteLink
				],
				$this->getRegularItem()
			],
			'Empty sidebar gets amended' => [
				[ 'commonswiki' => $newCommonsLink ],
				[],
				$this->getRegularItem()
			],
			'Existing commons link gets amended' => [
				[
					'enwikiquote' => $wikiquoteLink,
					'commonswiki' => $newCommonsLink
				],
				[
					'enwikiquote' => $wikiquoteLink,
					'commonswiki' => $oldCommonsLink
				],
				$this->getRegularItem()
			],
			'Invalid data value' => [
				[
					'enwikiquote' => $wikiquoteLink,
					'commonswiki' => $oldCommonsLink
				],
				[
					'enwikiquote' => $wikiquoteLink,
					'commonswiki' => $oldCommonsLink
				],
				$this->getInvalidSnakItem()
			]
		];
	}

	public function testDoAddToSidebar_disabled() {
		$handler = new WikibaseClientSiteLinksForItemHandler( null );

		$sidebar = [ '101010' => new SiteLink( '101010', 'blah' ) ];
		$origSidebar = $sidebar;
		$handler->doProvideSiteLinks( $this->getRegularItem(), $sidebar );
		$this->assertSame( $origSidebar, $sidebar );
	}

	/**
	 * @dataProvider constructor_invalidSettingProvider
	 */
	public function testConstructor_invalidSetting( $value ) {
		$this->expectException( ParameterTypeException::class );

		new WikibaseClientSiteLinksForItemHandler( $value );
	}

	public function constructor_invalidSettingProvider() {
		return [
			[ [ ':(' ] ],
			[ function () {
			} ],
			[ false ],
		];
	}

	public function testAddToSidebar() {
		// Integration test: Make sure this doesn't fatal
		$this->setMwGlobals( 'wgWikimediaBadgesCommonsCategoryProperty', null );
		$sidebar = [];

		WikibaseClientSiteLinksForItemHandler::provideSiteLinks(
			new Item( new ItemId( 'Q38434234' ) ),
			$sidebar,
			$this->createMock( UsageAccumulator::class )
		);
		// No exception thrown
		$this->assertTrue( true );
	}

	private function getRegularItem() {
		$propertyId = new PropertyId( 'P373' );
		$item = new Item( new ItemId( 'Q123' ) );
		$item->getStatements()->addNewStatement( new PropertyValueSnak( $propertyId, new StringValue( 'Amsterdam' ) ) );
		$item->getStatements()->addNewStatement( new PropertySomeValueSnak( $propertyId ) );
		return $item;
	}

	private function getInvalidSnakItem() {
		$propertyId = new PropertyId( 'P12' );
		$mainSnak = new PropertyValueSnak( $propertyId, new DecimalValue( 1 ) );
		$item = new Item( new ItemId( 'Q123' ) );
		$item->getStatements()->addNewStatement( $mainSnak );
		return $item;
	}
}
