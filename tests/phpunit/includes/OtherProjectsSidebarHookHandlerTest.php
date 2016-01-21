<?php

namespace WikimediaBadges\Tests;

use DataValues\StringValue;
use DataValues\DecimalValue;
use MediaWikiTestCase;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Services\Lookup\InMemoryEntityLookup;
use Wikibase\DataModel\Services\Lookup\EntityLookupException;
use WikimediaBadges\OtherProjectsSidebarHookHandler;

/**
 * @covers WikimediaBadges\OtherProjectsSidebarHookHandler
 *
 * @group WikimediaBadges
 *
 * @license GNU GPL v2+
 * @author Marius Hoch < hoo@online.de >
 */
class OtherProjectsSidebarHookHandlerTest extends MediaWikiTestCase {

	/**
	 * @dataProvider doAddToSidebarProvider
	 */
	public function testDoAddToSidebar( array $expected, array $sidebar, ItemId $itemId, $suppressErrors = false ) {
		$handler = new OtherProjectsSidebarHookHandler(
			$this->getEntityLookup(),
			'P373'
		);

		if ( $suppressErrors === 'suppress' ) {
			\MediaWiki\suppressWarnings();
		}
		$this->assertTrue( $handler->doAddToSidebar( $itemId, $sidebar ) );
		if ( $suppressErrors === 'suppress' ) {
			\MediaWiki\restoreWarnings();
		}

		$this->assertSame( $expected, $sidebar );
	}

	public function doAddToSidebarProvider() {
		$wikiquoteLink = array(
			'msg' => 'wikibase-otherprojects-wikiquote',
			'class' => 'wb-otherproject-link wb-otherproject-wikiquote',
			'href' => 'https://en.wikiquote.org/wiki/Ams',
			'hreflang' => 'en'
		);
		$oldCommonsLink = array(
			'msg' => 'wikibase-otherprojects-commons',
			'class' => 'wb-otherproject-link wb-otherproject-commons',
			'href' => 'https://commons.wikimedia.org/wiki/Amsterdam',
			'hreflang' => 'en'
		);
		$newCommonsLink = $oldCommonsLink;
		$newCommonsLink['href'] = 'https://commons.wikimedia.org/wiki/Category:Amsterdam';

		return array(
			'Item without commons category statement' => array(
				array(),
				array(),
				new ItemId( 'Q2013' )
			),
			'Sidebar without commons link gets amended' => array(
				array(
					'wikiquote' => array( 'enwikiquote' => $wikiquoteLink ),
					'commons' => array( 'commonswiki' => $newCommonsLink )
				),
				array(
					'wikiquote' => array( 'enwikiquote' => $wikiquoteLink )
				),
				new ItemId( 'Q123' )
			),
			'Empty sidebar gets amended' => array(
				array( 'commons' => array( 'commonswiki' => $newCommonsLink ) ),
				array(),
				new ItemId( 'Q123' )
			),
			'Existing commons link gets amended' => array(
				array(
					'wikiquote' => array( 'enwikiquote' => $wikiquoteLink ),
					'commons' => array( 'commonswiki' => $newCommonsLink )
				),
				array(
					'wikiquote' => array( 'enwikiquote' => $wikiquoteLink ),
					'commons' => array( 'commonswiki' => $oldCommonsLink )
				),
				new ItemId( 'Q123' )
			),
			'No such item' => array(
				array(
					'wikiquote' => array( 'enwikiquote' => $wikiquoteLink ),
					'commons' => array( 'commonswiki' => $oldCommonsLink )
				),
				array(
					'wikiquote' => array( 'enwikiquote' => $wikiquoteLink ),
					'commons' => array( 'commonswiki' => $oldCommonsLink )
				),
				new ItemId( 'Q404' )
			),
			'Item loading failed' => array(
				array(
					'wikiquote' => array( 'enwikiquote' => $wikiquoteLink ),
					'commons' => array( 'commonswiki' => $oldCommonsLink )
				),
				array(
					'wikiquote' => array( 'enwikiquote' => $wikiquoteLink ),
					'commons' => array( 'commonswiki' => $oldCommonsLink )
				),
				new ItemId( 'Q503' ),
				'suppress'
			),
		);
	}

	public function testDoAddToSidebar_disabled() {
		$entityLookup = $this->getMock( 'Wikibase\DataModel\Services\Lookup\EntityLookup' );
		$entityLookup->expects( $this->never() )
			->method( 'getEntity' );

		$handler = new OtherProjectsSidebarHookHandler(
			$entityLookup,
			null
		);

		$sidebar = array( 101010 => array( 'blah' ) );
		$origSidebar = $sidebar;
		$this->assertTrue( $handler->doAddToSidebar( new ItemId( 'Q42' ), $sidebar ) );
		$this->assertSame( $origSidebar, $sidebar );
	}

	/**
	 * @dataProvider constructor_invalidSettingProvider
	 */
	public function testConstructor_invalidSetting( $value ) {
		$this->setExpectedException( 'Wikimedia\Assert\ParameterTypeException' );

		new OtherProjectsSidebarHookHandler(
			$this->getMock( 'Wikibase\DataModel\Services\Lookup\EntityLookup' ),
			$value
		);
	}

	public function constructor_invalidSettingProvider() {
		return array(
			array( array( ':(' ) ),
			array( function() {} ),
			array( false )
		);
	}

	public function testDoAddToSidebar_invalidDataValue() {
		$entityLookup = new InMemoryEntityLookup();
		$propertyId = new PropertyId( 'P12' );
		$mainSnak = new PropertyValueSnak( $propertyId, new DecimalValue( 1 ) );

		$item = new Item( new ItemId( 'Q123' ) );
		$item->getStatements()->addNewStatement( $mainSnak );
		$entityLookup->addEntity( $item );

		$handler = new OtherProjectsSidebarHookHandler(
			$entityLookup,
			'P12'
		);

		$sidebar = array( 101010 => array( 'blah' ) );
		$origSidebar = $sidebar;

		\MediaWiki\suppressWarnings();
		$this->assertTrue( $handler->doAddToSidebar( new ItemId( 'Q123' ), $sidebar ) );
		\MediaWiki\restoreWarnings();

		$this->assertSame( $origSidebar, $sidebar );
	}

	public function testAddToSidebar() {
		// Integration test: Make sure this doesn't fatal
		$this->setMwGlobals( 'wgWikimediaBadgesCommonsCategoryProperty', null );
		$sidebar = array();

		$this->assertTrue(
			OtherProjectsSidebarHookHandler::addToSidebar( new ItemId( 'Q38434234' ), $sidebar )
		);
	}

	private function getEntityLookup() {
		$entityLookup = new InMemoryEntityLookup();
		$propertyId = new PropertyId( 'P373' );

		$mainSnak = new PropertyValueSnak( $propertyId, new StringValue( 'Amsterdam' ) );

		$item = new Item( new ItemId( 'Q123' ) );
		$item->getStatements()->addNewStatement( $mainSnak );
		$item->getStatements()->addNewStatement( new PropertySomeValueSnak( $propertyId ) );

		$exception = new EntityLookupException( new ItemId( 'Q503' ) );

		$entityLookup->addEntity( $item );
		$entityLookup->addEntity( new Item( new ItemId( 'Q2013' ) ) );
		$entityLookup->addException( $exception );

		return $entityLookup;
	}

}
