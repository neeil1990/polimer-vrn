<?php

namespace Yandex\Market\Export\Entity;

use Bitrix\Main;
use Yandex\Market;

class Facade
{
	public static function loadValues($productIds, array $select, array $context = [])
	{
		$elements = static::loadElements($productIds);
		$elementsByIblock = static::splitElementsByIblock($elements);
		$result = [];
		
		foreach ($elementsByIblock as $iblockId => $iblockElements)
		{
			$sourceFetcher = new Market\Export\Routine\QueryBuilder\SourceFetcher();
			$selectBuilder = new Market\Export\Routine\QueryBuilder\Select();

			if (static::hasOffers($iblockElements))
			{
				$parentIblockId = static::getOfferParentIblockId($iblockId);
				$iblockElements = static::markElementParents($iblockId, $iblockElements);
				$parentElements = static::combineParents($parentIblockId, $iblockElements);
				$iblockContext = $context + Market\Export\Entity\Iblock\Provider::getContext($parentIblockId);
			}
			else
			{
				$parentElements = [];
				$iblockContext = $context + Market\Export\Entity\Iblock\Provider::getContext($iblockId);
			}

			$iblockSelect = $selectBuilder->boot($select, $iblockContext);

			$result += $sourceFetcher->load($iblockSelect, $iblockElements, $parentElements, $iblockContext);

			$selectBuilder->release($iblockSelect, $iblockContext);
		}

		return $result;
	}

	protected static function loadElements($productIds)
	{
		$result = [];

		if (!Main\Loader::includeModule('iblock')) { return $result; }

		foreach (array_chunk($productIds, 500) as $productChunk)
		{
			$query = \CIBlockElement::GetList(
				[ 'ID' => 'ASC' ],
				[ '=ID' => $productChunk ],
				false,
				false,
				[
					'IBLOCK_ID',
					'ID',
					static::getCatalogTypeField(),
				]
			);

			while ($row = $query->Fetch())
			{
				$result[$row['ID']] = $row;
			}
		}

		return $result;
	}

	protected static function splitElementsByIblock($elements)
	{
		$result = [];

		foreach ($elements as $elementId => $element)
		{
			$iblockId = (int)$element['IBLOCK_ID'];

			if (!isset($result[$iblockId]))
			{
				$result[$iblockId] = [];
			}

			$result[$iblockId][$elementId] = $element;
		}

		return $result;
	}

	protected static function hasOffers($elements)
	{
		$typeField = static::getCatalogTypeField();
		$result = false;

		foreach ($elements as $element)
		{
			if (
				isset($element[$typeField])
				&& (int)$element[$typeField] === Market\Export\Run\Steps\Offer::ELEMENT_TYPE_OFFER
			)
			{
				$result = true;
				break;
			}
		}

		return $result;
	}

	protected static function markElementParents($iblockId, $elements)
	{
		if (!Main\Loader::includeModule('catalog')) { return $elements; }

		$catalogInfo = \CCatalogSku::GetInfoByIBlock($iblockId);

		if (!isset($catalogInfo['CATALOG_TYPE']) || $catalogInfo['CATALOG_TYPE'] !== \CCatalogSku::TYPE_OFFERS) { return $elements; }
		if (empty($catalogInfo['SKU_PROPERTY_ID']) || (int)$iblockId !== $catalogInfo['IBLOCK_ID']) { return $elements; }

		$skuPropertyId = $catalogInfo['SKU_PROPERTY_ID'];

		$query = \CIBlockElement::GetPropertyValues(
			$iblockId,
			[ '=ID' => array_keys($elements) ],
			false,
			[ 'ID' => $catalogInfo['SKU_PROPERTY_ID'] ]
		);

		while ($row = $query->Fetch())
		{
			if (empty($row[$skuPropertyId])) { continue; }

			$elementId = (int)$row['IBLOCK_ELEMENT_ID'];
			$parentId = (int)$row[$skuPropertyId];

			if (!isset($elements[$elementId])) { continue; }

			$elements[$elementId]['PARENT_ID'] = $parentId;
		}

		return $elements;
	}

	protected static function combineParents($iblockId, $elements)
	{
		$result = [];

		foreach ($elements as $element)
		{
			if (empty($element['PARENT_ID'])) { continue; }

			$parentId = $element['PARENT_ID'];

			if (isset($result[$parentId])) { continue; }

			$result[$parentId] = [
				'IBLOCK_ID' => $iblockId,
				'ID' => $parentId,
			];
		}

		return $result;
	}

	protected static function getOfferParentIblockId($iblockId)
	{
		if (!Main\Loader::includeModule('catalog')) { return $iblockId; }

		$catalogInfo = \CCatalogSku::GetInfoByIBlock($iblockId);

		return isset($catalogInfo['PRODUCT_IBLOCK_ID'])
			? (int)$catalogInfo['PRODUCT_IBLOCK_ID']
			: $iblockId;
	}

	protected static function getCatalogTypeField()
	{
		return Catalog\Provider::useCatalogShortFields() ? 'TYPE' : 'CATALOG_TYPE';
	}
}
