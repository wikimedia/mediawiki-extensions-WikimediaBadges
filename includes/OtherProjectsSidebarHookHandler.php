<?php

namespace WikimediaBadges;

use DataValues\StringValue;
use RequestContext;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\DataModel\Services\Lookup\EntityLookupException;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\Client\WikibaseClient;
use Wikimedia\Assert\Assert;
use Wikimedia\Assert\ParameterTypeException;

/**
 * Handler for the WikibaseClientOtherProjectsSidebar hook that changes the link
 * to Wikimedia Commons with the one to the commons category.
 *
 * @since 0.1
 *
 * @license GNU GPL v2+
 * @author Marius Hoch < hoo@online.de >
 */
class OtherProjectsSidebarHookHandler {

	/**
	 * @var EntityLookup
	 */
	private $entityLookup;

	/**
	 * @var string|null
	 */
	private $commonsCategoryPropertySetting;

	/**
	 * @return self
	 */
	private static function newFromGlobalState() {
		$wikibaseClient = WikibaseClient::getDefaultInstance();

		return new self(
			$wikibaseClient->getStore()->getEntityLookup(),
			RequestContext::getMain()->getConfig()->get( 'WikimediaBadgesCommonsCategoryProperty' )
		);
	}

	/**
	 * @param EntityLookup $entityLookup
	 * @param string|null $commonsCategoryPropertySetting
	 *
	 * @throws ParameterTypeException
	 */
	public function __construct( EntityLookup $entityLookup, $commonsCategoryPropertySetting ) {
		Assert::parameterType( 'string|null', $commonsCategoryPropertySetting, '$commonsCategoryPropertySetting' );

		$this->entityLookup = $entityLookup;
		$this->commonsCategoryPropertySetting = $commonsCategoryPropertySetting;
	}

	/**
	 * @since 0.1
	 *
	 * @param ItemId $itemId
	 * @param array &$sidebar
	 *
	 * @return bool
	 */
	public static function addToSidebar( ItemId $itemId, array &$sidebar ) {
		$self = self::newFromGlobalState();

		return $self->doAddToSidebar( $itemId, $sidebar );
	}

	/**
	 * @since 0.1
	 *
	 * @param ItemId $itemId
	 * @param array &$sidebar
	 *
	 * @return bool
	 */
	public function doAddToSidebar( ItemId $itemId, array &$sidebar ) {
		if ( $this->commonsCategoryPropertySetting !== null
		) {
			$categoryName = $this->getCommonsCategoryName( $itemId );
			if ( $categoryName !== null ) {
				$this->handleCategoryName( $categoryName, $sidebar );
			}
		}

		return true;
	}

	/**
	 * @param string $categoryName
	 * @param array &$sidebar
	 */
	private function handleCategoryName( $categoryName, array &$sidebar ) {
		$href = 'https://commons.wikimedia.org/wiki/Category:' .
			wfUrlencode( str_replace( ' ', '_', $categoryName ) );

		$this->modifyOrAddEntry( $href, $sidebar );
	}

	/**
	 * @param string $href Link to the commons category
	 * @param array &$sidebar
	 */
	private function modifyOrAddEntry( $href, array &$sidebar ) {
		if ( isset( $sidebar['commons']['commonswiki'] ) ) {
			$sidebar['commons']['commonswiki']['href'] = $href;

			return;
		}

		$sidebar['commons'] = array(
			'commonswiki' => array(
				'msg' => 'wikibase-otherprojects-commons',
				'class' => 'wb-otherproject-link wb-otherproject-commons',
				'href' => $href,
				'hreflang' => 'en'
			)
		);
	}

	/**
	 * @param ItemId $itemId
	 *
	 * @return string|null
	 */
	private function getCommonsCategoryName( ItemId $itemId ) {
		$item = $this->getItem( $itemId );

		if ( !$item ) {
			return null;
		}

		return $this->getCommonsCategoryNameFromItem( $item );
	}

	/**
	 * @param Item $item
	 *
	 * @return string|null
	 */
	private function getCommonsCategoryNameFromItem( Item $item ) {
		$propertyId = new PropertyId( $this->commonsCategoryPropertySetting );
		$statements = $item->getStatements()->getByPropertyId( $propertyId );

		$mainSnaks = $statements->getBestStatements()->getMainSnaks();

		return $this->getCommonsCategoryNameFromMainSnaks(
			$mainSnaks,
			$item->getId(),
			$propertyId
		);
	}

	/**
	 * @param Snak[] $mainSnaks
	 * @param ItemId $itemId
	 * @param PropertyId $propertyId
	 *
	 * @return string|null
	 */
	private function getCommonsCategoryNameFromMainSnaks(
		array $mainSnaks,
		ItemId $itemId,
		PropertyId $propertyId
	) {
		foreach ( $mainSnaks as $snak ) {
			if ( !( $snak instanceof PropertyValueSnak ) ) {
				continue;
			}

			if ( !( $snak->getDataValue() instanceof StringValue ) ) {
				wfLogWarning(
					$itemId->getSerialization() . ' has a PropertyValueSnak with ' .
						$propertyId->getSerialization() . ' that has non-StringValue data.'
				);

				continue;
			}

			return $snak->getDataValue()->getValue();
		}

		return null;
	}

	/**
	 * @param ItemId $itemId
	 *
	 * @return Item|null
	 */
	private function getItem( ItemId $itemId ) {
		try {
			$item = $this->entityLookup->getEntity( $itemId );
		} catch( EntityLookupException $ex ) {
			wfLogWarning(
				"Failed to load Item $itemId: " . $ex->getMessage()
			);

			return null;
		}

		return $item;
	}

}
