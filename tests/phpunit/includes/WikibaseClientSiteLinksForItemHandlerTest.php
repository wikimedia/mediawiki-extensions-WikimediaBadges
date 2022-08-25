<?php

declare( strict_types = 1 );

namespace WikimediaBadges\Tests;

use DataValues\DecimalValue;
use MediaWikiIntegrationTestCase;
use Wikibase\Client\Usage\UsageAccumulator;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\DataModel\Services\Lookup\InMemoryEntityLookup;
use Wikibase\DataModel\SiteLink;
use Wikibase\DataModel\Tests\NewItem;
use Wikibase\DataModel\Tests\NewStatement;
use WikimediaBadges\WikibaseClientSiteLinksForItemHandler;

/**
 * @covers \WikimediaBadges\WikibaseClientSiteLinksForItemHandler
 *
 * @group WikimediaBadges
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch < hoo@online.de >
 */
class WikibaseClientSiteLinksForItemHandlerTest extends MediaWikiIntegrationTestCase {

	/**
	 * @dataProvider doAddToSidebarProvider
	 */
	public function testDoAddToSidebar(
		array $expected,
		array $sidebar,
		Item $item,
		EntityLookup $entityLookup
	) {
		$handler = new WikibaseClientSiteLinksForItemHandler(
			$entityLookup,
			'P910',
			'P1754',
			'P373'
		);
		$handler->doProvideSiteLinks( $item, $sidebar );
		$this->assertEquals( $expected, $sidebar );
	}

	public function doAddToSidebarProvider() {
		$wikiquoteLink = new SiteLink( 'enwikiquote', 'Ams' );
		$oldCommonsLink = new SiteLink( 'commonswiki', 'Amsterdam' );
		$newCommonsLink = new SiteLink( 'commonswiki', 'Category:Amsterdam' );
		$unusedEntityLookup = $this->createMock( EntityLookup::class );
		$unusedEntityLookup->expects( $this->never() )
			->method( 'getEntity' );
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

		yield 'Item without commons category statement' => [
			[],
			[],
			new Item( new ItemId( 'Q42' ) ),
			$unusedEntityLookup
		];

		yield 'Sidebar without commons link gets amended' => [
			[
				'enwikiquote' => $wikiquoteLink,
				'commonswiki' => $newCommonsLink
			],
			[
				'enwikiquote' => $wikiquoteLink
			],
			$this->getRegularItem(),
			$unusedEntityLookup
		];

		yield 'Empty sidebar gets amended' => [
			[ 'commonswiki' => $newCommonsLink ],
			[],
			$this->getRegularItem(),
			$unusedEntityLookup
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
			$this->getRegularItem(),
			$unusedEntityLookup
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
			$this->getInvalidSnakItem(),
			$unusedEntityLookup
		];

		yield 'Own sitelink' => [
			[ 'commonswiki' => $newCommonsLink ],
			[],
			NewItem::withId( 'Q123' )
				->andSiteLink( 'commonswiki', 'Category:Amsterdam' )
				->build(),
			$unusedEntityLookup,
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
			$entityLookup,
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
			$entityLookup,
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
			$entityLookup,
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
			$entityLookup,
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
			$entityLookup,
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
			$entityLookup,
		];
	}

	public function testDoAddToSidebar_disabled() {
		$handler = new WikibaseClientSiteLinksForItemHandler(
			new InMemoryEntityLookup(),
			null,
			null,
			null
		);

		$sidebar = [ '101010' => new SiteLink( '101010', 'blah' ) ];
		$origSidebar = $sidebar;
		$handler->doProvideSiteLinks( $this->getRegularItem(), $sidebar );
		$this->assertSame( $origSidebar, $sidebar );
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
		return NewItem::withId( 'Q123' )
			->andStatement(
				NewStatement::forProperty( 'P373' )
					->withValue( 'Amsterdam' )
			)
			->andStatement( NewStatement::someValueFor( 'P373' ) )
			->build();
	}

	private function getInvalidSnakItem() {
		return NewItem::withId( 'Q123' )
			->andStatement(
				NewStatement::forProperty( 'P12' )
					->withValue( new DecimalValue( 1 ) )
			)
			->build();
	}
}
