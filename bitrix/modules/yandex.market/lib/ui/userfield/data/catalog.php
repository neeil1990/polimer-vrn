<?php

namespace Yandex\Market\Ui\UserField\Data;

use Yandex\Market;
use Bitrix\Main;
use Bitrix\Catalog as CatalogModule;

class Catalog
{
	use Market\Reference\Concerns\HasLang;

	const TYPE_PRODUCT = 'PRODUCT';
	const TYPE_OFFERS = 'OFFERS';

	protected static function includeMessages()
	{
		Main\Localization\Loc::loadMessages(__FILE__);
	}

	public static function getTypeTitle($type, $version = '')
	{
		$suffix = ($version === '' ? $version : '_' . $version);

		return static::getLang('USER_FIELD_DATA_CATALOG_TYPE_' . $type . $suffix, null, $type);
	}

	public static function getIblockTypes($iblockIds = null)
	{
		$result = [];

		if (Main\Loader::includeModule('catalog'))
		{
			$filter = [];

			if ($iblockIds !== null)
			{
				$filter['=IBLOCK_ID'] = $iblockIds;
			}

			$queryCatalogList = CatalogModule\CatalogIblockTable::getList([
				'filter' => $filter,
				'select' => [ 'IBLOCK_ID', 'PRODUCT_IBLOCK_ID' ],
			]);

			while ($catalog = $queryCatalogList->fetch())
			{
				$iblockId = (int)$catalog['IBLOCK_ID'];
				$productIblockId = (int)$catalog['PRODUCT_IBLOCK_ID'];

				if ($productIblockId > 0 && $productIblockId !== $iblockId)
				{
					$result[$productIblockId] = static::TYPE_PRODUCT;
					$result[$iblockId] = static::TYPE_OFFERS;
				}
				else
				{
					$result[$iblockId] = static::TYPE_PRODUCT;
				}
			}
		}

		return $result;
	}

	public static function groupEnum($enum, $typesMap, $titleVersion = '')
	{
		$optionsCount = count($enum);
		$usedTypes = array_unique($typesMap);
		$typesSort = static::makeTypesSort($usedTypes, $optionsCount);
		$enumSort = [];

		foreach ($enum as $optionKey => &$option)
		{
			$optionId = $option['ID'];
			$optionType = isset($typesMap[$optionId]) ? $typesMap[$optionId] : 'DEFAULT';

			$option['GROUP_CODE'] = $optionType;
			$option['GROUP'] = static::getTypeTitle($optionType, $titleVersion);

			$enumSort[$optionId] = $typesSort[$optionType];

			$typesSort[$optionType]++;
		}
		unset($option);

		usort($enum, function($optionA, $optionB) use ($enumSort) {
			$sortA = $enumSort[$optionA['ID']];
			$sortB = $enumSort[$optionB['ID']];

			return $sortA < $sortB ? -1 : 1;
		});

		return $enum;
	}

	protected static function makeTypesSort($supportedTypes, $optionsCount)
	{
		$result = [];
		$typeIndex = 0;

		foreach ($supportedTypes as $type)
		{
			$result[$type] = $typeIndex * $optionsCount;

			++$typeIndex;
		}

		$result['DEFAULT'] = $typeIndex * $optionsCount;

		return $result;
	}
}