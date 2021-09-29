<?php

declare( strict_types = 1 );

namespace WikimediaBadges;

use DataValues\StringValue;
use MediaWiki\MediaWikiServices;
use OutOfBoundsException;
use Wikibase\Client\Usage\UsageAccumulator;
use Wikibase\Client\WikibaseClient;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\DataModel\Services\Lookup\EntityLookupException;
use Wikibase\DataModel\SiteLink;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;

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

	/** @var EntityLookup */
	private $entityLookup;

	/** @var string|null */
	private $topicsMainCategoryProperty;

	/** @var string|null */
	private $categoryRelatedToListProperty;

	/**
	 * @var string|null
	 */
	private $commonsCategoryPropertySetting;

	private static function newFromGlobalState(): self {
		$services = MediaWikiServices::getInstance();
		$config = $services->getMainConfig();

		return new self(
			WikibaseClient::getEntityLookup( $services ),
			$config->get( 'WikimediaBadgesTopicsMainCategoryProperty' ),
			$config->get( 'WikimediaBadgesCategoryRelatedToListProperty' ),
			$config->get( 'WikimediaBadgesCommonsCategoryProperty' )
		);
	}

	public function __construct(
		EntityLookup $entityLookup,
		?string $topicsMainCategoryProperty,
		?string $categoryRelatedToListProperty,
		?string $commonsCategoryPropertySetting
	) {
		$this->entityLookup = $entityLookup;
		$this->topicsMainCategoryProperty = $topicsMainCategoryProperty;
		$this->categoryRelatedToListProperty = $categoryRelatedToListProperty;
		$this->commonsCategoryPropertySetting = $commonsCategoryPropertySetting;
	}

	/**
	 * @param Item $item
	 * @param SiteLink[] &$siteLinks
	 * @param UsageAccumulator $usageAccumulator
	 */
	public static function provideSiteLinks(
		Item $item, array &$siteLinks, UsageAccumulator $usageAccumulator
	): void {
		$self = self::newFromGlobalState();

		$self->doProvideSiteLinks( $item, $siteLinks );
	}

	/**
	 * @param Item $item
	 * @param SiteLink[] &$siteLinks
	 */
	public function doProvideSiteLinks( Item $item, array &$siteLinks ): void {
		$sitelink = $this->getCommonsSiteLink( $item );
		if ( $sitelink !== null ) {
			$this->addSiteLink( $sitelink, $siteLinks );
		}
	}

	/**
	 * @param string $siteLink
	 * @param SiteLink[] &$siteLinks
	 */
	private function addSiteLink( string $siteLink, array &$siteLinks ): void {
		$siteLinks['commonswiki'] = new SiteLink( 'commonswiki', $siteLink );
	}

	private function getCommonsSiteLink( Item $item ): ?string {
		try {
			return $item->getSiteLink( 'commonswiki' )->getPageName();
		} catch ( OutOfBoundsException $e ) {
			// pass
		}

		$topicsMainCategorySitelink = $this->getLinkedItemSitelink(
			$item,
			$this->topicsMainCategoryProperty
		);
		if ( $topicsMainCategorySitelink !== null ) {
			return $topicsMainCategorySitelink;
		}

		$categoryRelatedToListSitelink = $this->getLinkedItemSitelink(
			$item,
			$this->categoryRelatedToListProperty
		);
		if ( $categoryRelatedToListSitelink !== null ) {
			return $categoryRelatedToListSitelink;
		}

		$categoryName = $this->getCommonsCategoryName( $item );
		if ( $categoryName !== null ) {
			return 'Category:' . $categoryName;
		}

		return null;
	}

	private function getLinkedItemSitelink( Item $item, ?string $propertyIdString ): ?string {
		if ( $propertyIdString === null ) {
			return null;
		}

		$propertyId = new NumericPropertyId( $propertyIdString );
		$statements = $item->getStatements()->getByPropertyId( $propertyId );

		$mainSnaks = $statements->getBestStatements()->getMainSnaks();

		return $this->getCommonsSitelinkFromMainSnaks(
			$mainSnaks,
			$item->getId(),
			$propertyId
		);
	}

	private function getCommonsCategoryName( Item $item ): ?string {
		if ( $this->commonsCategoryPropertySetting === null ) {
			return null;
		}

		$propertyId = new NumericPropertyId( $this->commonsCategoryPropertySetting );
		$statements = $item->getStatements()->getByPropertyId( $propertyId );

		$mainSnaks = $statements->getBestStatements()->getMainSnaks();

		return $this->getStringValueFromMainSnaks(
			$mainSnaks,
			$item->getId(),
			$propertyId
		);
	}

	private function getCommonsSitelinkFromMainSnaks(
		array $mainSnaks,
		ItemId $itemId,
		NumericPropertyId $propertyId
	): ?string {
		foreach ( $mainSnaks as $snak ) {
			if ( !( $snak instanceof PropertyValueSnak ) ) {
				continue;
			}

			$dataValue = $snak->getDataValue();
			if ( !(
				$dataValue instanceof EntityIdValue &&
				$dataValue->getEntityId() instanceof ItemId
			) ) {
				wfLogWarning(
					$itemId->getSerialization() . ' has a PropertyValueSnak with ' .
					$propertyId->getSerialization() . ' that has non-ItemId data.'
				);

				continue;
			}
			$itemId = $dataValue->getEntityId();
			'@phan-var ItemId $itemId';

			try {
				$item = $this->getItem( $itemId );
			} catch ( EntityLookupException $e ) {
				continue;
			}
			if ( $item === null ) {
				continue;
			}

			try {
				return $item->getSiteLink( 'commonswiki' )->getPageName();
			} catch ( OutOfBoundsException $e ) {
				continue;
			}
		}

		return null;
	}

	/** @throws EntityLookupException */
	private function getItem( ItemId $itemId ): ?Item {
		return $this->entityLookup->getEntity( $itemId );
	}

	/**
	 * @param Snak[] $mainSnaks
	 * @param ItemId $itemId
	 * @param NumericPropertyId $propertyId
	 *
	 * @return string|null
	 */
	private function getStringValueFromMainSnaks(
		array $mainSnaks,
		ItemId $itemId,
		NumericPropertyId $propertyId
	): ?string {
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
