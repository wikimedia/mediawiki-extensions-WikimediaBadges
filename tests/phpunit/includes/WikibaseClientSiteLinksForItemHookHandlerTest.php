<?php

declare( strict_types = 1 );

namespace WikimediaBadges\Tests;

use DataValues\DecimalValue;
use MediaWiki\Config\Config;
use MediaWiki\Config\HashConfig;
use MediaWikiIntegrationTestCase;
use Wikibase\Client\Usage\UsageAccumulator;
use Wikibase\Client\WikibaseClient;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\DataModel\Services\Lookup\InMemoryEntityLookup;
use Wikibase\DataModel\SiteLink;
use Wikibase\DataModel\Tests\NewItem;
use Wikibase\DataModel\Tests\NewStatement;
use WikimediaBadges\WikibaseClientSiteLinksForItemHookHandler;

/**
 * @covers \WikimediaBadges\WikibaseClientSiteLinksForItemHookHandler
 *
 * @group WikimediaBadges
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch < hoo@online.de >
 */
class WikibaseClientSiteLinksForItemHookHandlerTest extends MediaWikiIntegrationTestCase {

	/**
	 * @dataProvider doAddToSidebarProvider
	 */
	public function testDoAddToSidebar(
		array $expected,
		array $sidebar,
		Item $item,
		$mockTypEntityLookup
	) {
		if ( $mockTypEntityLookup === 'noop' ) {
			$entityLookup = $this->createMock( EntityLookup::class );
			$entityLookup->expects( $this->never() )
				->method( 'getEntity' );
		} else {
			$itemWithCategoryAmsterdamSitelink = NewItem::withId( 'Q456' )
				->andSiteLink( 'commonswiki', 'Category:Amsterdam' )
				->build();
			$itemWithAmsterdamSitelink = NewItem::withId( 'Q789' )
				->andSiteLink( 'commonswiki', 'Amsterdam' )
				->build();
			$entityLookup = new InMemoryEntityLookup(
				$itemWithCategoryAmsterdamSitelink,
				$itemWithAmsterdamSitelink
			);
		}
		$config = new HashConfig( [
			'WikimediaBadgesTopicsMainCategoryProperty' => 'P910',
			'WikimediaBadgesCategoryRelatedToListProperty' => 'P1754',
			'WikimediaBadgesCommonsCategoryProperty' => 'P373'
		] );
		$handler = new WikibaseClientSiteLinksForItemHookHandler(
			$config,
			$entityLookup,
		);
		$usageAccumulator = $this->createMock( UsageAccumulator::class );
		$handler->onWikibaseClientSiteLinksForItem( $item, $sidebar, $usageAccumulator );
		$this->assertEquals( $expected, $sidebar );
	}

	public static function doAddToSidebarProvider() {
		$wikiquoteLink = new SiteLink( 'enwikiquote', 'Ams' );
		$oldCommonsLink = new SiteLink( 'commonswiki', 'Amsterdam' );
		$newCommonsLink = new SiteLink( 'commonswiki', 'Category:Amsterdam' );

		yield 'Item without commons category statement' => [
			[],
			[],
			new Item( new ItemId( 'Q42' ) ),
			'noop',
		];

		yield 'Sidebar without commons link gets amended' => [
			[
				'enwikiquote' => $wikiquoteLink,
				'commonswiki' => $newCommonsLink
			],
			[
				'enwikiquote' => $wikiquoteLink
			],
			self::getRegularItem(),
			'noop',
		];

		yield 'Empty sidebar gets amended' => [
			[ 'commonswiki' => $newCommonsLink ],
			[],
			self::getRegularItem(),
			'noop',
		];

		yield 'Existing commons link gets amended' => [
			[
				'enwikiquote' => $wikiquoteLink,
				'commonswiki' => $newCommonsLink
			],
			[
				'enwikiquote' => $wikiquoteLink,
				'commonswiki' => $oldCommonsLink
			],
			self::getRegularItem(),
			'noop',
		];

		yield 'Invalid data value' => [
			[
				'enwikiquote' => $wikiquoteLink,
				'commonswiki' => $oldCommonsLink
			],
			[
				'enwikiquote' => $wikiquoteLink,
				'commonswiki' => $oldCommonsLink
			],
			self::getInvalidSnakItem(),
			'noop',
		];

		yield 'Own sitelink' => [
			[ 'commonswiki' => $newCommonsLink ],
			[],
			NewItem::withId( 'Q123' )
				->andSiteLink( 'commonswiki', 'Category:Amsterdam' )
				->build(),
			'noop',
		];

		yield "Topic's main category statement" => [
			[ 'commonswiki' => $newCommonsLink ],
			[],
			NewItem::withId( 'Q123' )
				->andStatement(
					NewStatement::forProperty( 'P910' )
						->withValue( new ItemId( 'Q456' ) )
				)
				->build(),
			'inmemory',
		];

		yield 'Category related to list statement' => [
			[ 'commonswiki' => $newCommonsLink ],
			[],
			NewItem::withId( 'Q123' )
				->andStatement(
					NewStatement::forProperty( 'P1754' )
						->withValue( new ItemId( 'Q456' ) )
				)
				->build(),
			'inmemory',
		];

		yield "Own sitelink > Topic's main category" => [
			[ 'commonswiki' => $newCommonsLink ],
			[],
			NewItem::withId( 'Q123' )
				->andSiteLink( 'commonswiki', 'Category:Amsterdam' )
				->andStatement(
					NewStatement::forProperty( 'P910' )
						->withValue( new ItemId( 'Q789' ) )
				)
				->build(),
			'inmemory',
		];

		yield "Topic's main category > Category related to list" => [
			[ 'commonswiki' => $newCommonsLink ],
			[],
			NewItem::withId( 'Q123' )
				->andStatement(
					NewStatement::forProperty( 'P910' )
						->withValue( new ItemId( 'Q456' ) )
				)
				->andStatement(
					NewStatement::forProperty( 'P1754' )
						->withValue( new ItemId( 'Q789' ) )
				)
				->build(),
			'inmemory',
		];

		yield 'Category related to list > Commons category' => [
			[ 'commonswiki' => $newCommonsLink ],
			[],
			NewItem::withId( 'Q123' )
				->andStatement(
					NewStatement::forProperty( 'P1754' )
						->withValue( new ItemId( 'Q456' ) )
				)
				->andStatement(
					NewStatement::forProperty( 'P373' )
						->withValue( 'Not Amsterdam' )
				)
				->build(),
			'inmemory',
		];

		yield "Topic's main category linking to missing item => Category related to list" => [
			[ 'commonswiki' => $newCommonsLink ],
			[],
			NewItem::withId( 'Q123' )
				->andStatement(
					NewStatement::forProperty( 'P910' )
						->withValue( new ItemId( 'Q1000' ) )
				)
				->andStatement(
					NewStatement::forProperty( 'P1754' )
						->withValue( new ItemId( 'Q456' ) )
				)
			->build(),
			'inmemory',
		];
	}

	public function testDoAddToSidebar_disabled() {
		$handler = new WikibaseClientSiteLinksForItemHookHandler(
			$this->createConfiguredMock( Config::class, [ 'get' => null ] ),
			new InMemoryEntityLookup()
		);

		$sidebar = [ '101010' => new SiteLink( '101010', 'blah' ) ];
		$origSidebar = $sidebar;
		$usageAccumulator = $this->createMock( UsageAccumulator::class );
		$handler->onWikibaseClientSiteLinksForItem( self::getRegularItem(), $sidebar, $usageAccumulator );
		$this->assertSame( $origSidebar, $sidebar );
	}

	public function testAddToSidebar() {
		// Integration test: Make sure this doesn't fatal
		$this->overrideConfigValue( 'WikimediaBadgesCommonsCategoryProperty', null );
		$sidebar = [];

		$handler = new WikibaseClientSiteLinksForItemHookHandler(
			$this->getServiceContainer()->getMainConfig(),
			WikibaseClient::getEntityLookup( $this->getServiceContainer() ),
		);

		$handler->onWikibaseClientSiteLinksForItem(
			new Item( new ItemId( 'Q38434234' ) ),
			$sidebar,
			$this->createMock( UsageAccumulator::class )
		);

		// No exception thrown
		$this->assertTrue( true );
	}

	private static function getRegularItem() {
		return NewItem::withId( 'Q123' )
			->andStatement(
				NewStatement::forProperty( 'P373' )
					->withValue( 'Amsterdam' )
			)
			->andStatement( NewStatement::someValueFor( 'P373' ) )
			->build();
	}

	private static function getInvalidSnakItem() {
		return NewItem::withId( 'Q123' )
			->andStatement(
				NewStatement::forProperty( 'P12' )
					->withValue( new DecimalValue( 1 ) )
			)
			->build();
	}
}
