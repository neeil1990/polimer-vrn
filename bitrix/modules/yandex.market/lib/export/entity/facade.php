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
			$iblockContext = $context + Market\Export\Entity\Iblock\Provider::getContext($iblockId);
			$iblockSelect = $select;

			static::initializeQueryContext($iblockContext, $iblockSelect);
			static::sortSourceSelect($iblockSelect);

			$result += static::fetchSourcesValues($iblockSelect, $iblockElements, [], $iblockContext);

			static::releaseQueryContext($iblockContext, $iblockSelect);
		}

		return $result;
	}

	protected static function loadElements($productIds)
	{
		$result = [];

		if (Main\Loader::includeModule('iblock'))
		{
			$query = \CIBlockElement::GetList(
				[ 'ID' => 'ASC' ],
				[ '=ID' => $productIds ],
				false,
				false,
				[
					'IBLOCK_ID',
					'ID',
					Catalog\Provider::useCatalogShortFields() ? 'TYPE' : 'CATALOG_TYPE'
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

	protected static function initializeQueryContext(&$iblockContext, &$sourceSelect)
	{
		foreach ($sourceSelect as $sourceType => $sourceFields)
		{
			$source = static::getSource($sourceType);

			$source->initializeQueryContext($sourceFields, $iblockContext, $sourceSelect);
		}
	}

	protected static function sortSourceSelect(&$sourceSelect)
	{
		$order = [];

		foreach ($sourceSelect as $sourceType => $sourceFields)
		{
			$source = static::getSource($sourceType);
			$order[$sourceType] = $source->getOrder();
		}

		uksort($sourceSelect, function($aType, $bType) use ($order) {
			$aOrder = $order[$aType];
			$bOrder = $order[$bType];

			if ($aOrder === $bOrder) { return 0; }

			return ($aOrder < $bOrder ? -1 : 1);
		});
	}

	protected static function fetchSourcesValues($sourceSelect, $elementList, $parentList, $queryContext)
	{
		$result = [];

		foreach ($sourceSelect as $sourceType => $sourceFields)
		{
			$source = static::getSource($sourceType);
			$sourceValues = $source->getElementListValues($elementList, $parentList, $sourceFields, $queryContext, $result);

			foreach ($sourceValues as $elementId => $elementValues)
			{
				if (!isset($result[$elementId]))
				{
					$result[$elementId] = [];
				}

				$result[$elementId][$sourceType] = $elementValues;
			}
		}

		return $result;
	}

	protected static function releaseQueryContext($iblockContext, $sourceSelect)
	{
		foreach ($sourceSelect as $sourceType => $sourceFields)
		{
			$source = static::getSource($sourceType);

			$source->releaseQueryContext($sourceFields, $iblockContext, $sourceSelect);
		}
	}

	/**
	 * Получить источник данных для выгрузки
	 *
	 * @param $type
	 *
	 * @return Market\Export\Entity\Reference\Source
	 * @throws \Bitrix\Main\ObjectNotFoundException
	 */
	protected static function getSource($type)
	{
		return Market\Export\Entity\Manager::getSource($type);
	}
}
