<?php

namespace WikimediaBadges;

use DataValues\StringValue;
use RequestContext;
use Wikibase\Client\Usage\UsageAccumulator;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\SiteLink;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;
use Wikimedia\Assert\Assert;
use Wikimedia\Assert\ParameterTypeException;

/**
 * Handler for the WikibaseClientSiteLinksForItem hook that changes the link
 * to Wikimedia Commons with the one to the commons category.
 *
 * @since 0.1
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch < hoo@online.de >
 */
class WikibaseClientSiteLinksForItemHandler {

	/**
	 * @var string|null
	 */
	private $commonsCategoryPropertySetting;

	/**
	 * @return self
	 */
	private static function newFromGlobalState() {
		return new self(
			RequestContext::getMain()->getConfig()->get( 'WikimediaBadgesCommonsCategoryProperty' )
		);
	}

	/**
	 * @param string|null $commonsCategoryPropertySetting
	 *
	 * @throws ParameterTypeException
	 */
	public function __construct( $commonsCategoryPropertySetting ) {
		Assert::parameterType(
			'string|null',
			$commonsCategoryPropertySetting,
			'$commonsCategoryPropertySetting'
		);

		$this->commonsCategoryPropertySetting = $commonsCategoryPropertySetting;
	}

	/**
	 * @param Item $item
	 * @param SiteLink[] &$siteLinks
	 * @param UsageAccumulator $usageAccumulator
	 */
	public static function provideSiteLinks(
		Item $item, array &$siteLinks, UsageAccumulator $usageAccumulator
	) {
		$self = self::newFromGlobalState();

		$self->doProvideSiteLinks( $item, $siteLinks );
	}

	/**
	 * @param Item $item
	 * @param SiteLink[] &$siteLinks
	 */
	public function doProvideSiteLinks( Item $item, array &$siteLinks ) {
		if ( $this->commonsCategoryPropertySetting !== null ) {
			$categoryName = $this->getCommonsCategoryName( $item );
			if ( $categoryName !== null ) {
				$this->handleCategoryName( $categoryName, $siteLinks );
			}
		}
	}

	/**
	 * @param string $categoryName
	 * @param SiteLink[] &$siteLinks
	 */
	private function handleCategoryName( $categoryName, array &$siteLinks ) {
		$siteLinks['commonswiki'] = new SiteLink( 'commonswiki', 'Category:' . $categoryName );
	}

	/**
	 * @param Item $item
	 *
	 * @return string|null
	 */
	private function getCommonsCategoryName( Item $item ) {
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
}
