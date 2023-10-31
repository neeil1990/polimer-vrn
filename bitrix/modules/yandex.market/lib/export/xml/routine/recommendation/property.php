<?php

namespace Yandex\Market\Export\Xml\Routine\Recommendation;

use Yandex\Market\Export;
use Bitrix\Main;
use Bitrix\Iblock;

class Property
{
	public static function userTypeValue(array $userTypes, array $context)
	{
		$filter = [
			'=USER_TYPE' => $userTypes,
		];

		return static::loadProperties($filter, $context);
	}

	public static function userTypeDescription(array $userTypes, array $context)
	{
		$filter = [
			'=USER_TYPE' => $userTypes,
			'=WITH_DESCRIPTION' => 'Y',
		];
		$fields = static::loadProperties($filter, $context);

		foreach ($fields as &$field)
		{
			$field['FIELD'] .= '.DESCRIPTION';
		}
		unset($field);

		return $fields;
	}

	public static function filter(array $filter, array $context)
	{
		return static::loadProperties($filter, $context);
	}

	protected static function loadProperties(array $filter, array $context)
	{
		if (empty($context['IBLOCK_ID']) || !Main\Loader::includeModule('iblock')) { return []; }

		$result = [];

		$iblockMap = [
			$context['IBLOCK_ID'] => Export\Entity\Manager::TYPE_IBLOCK_ELEMENT_PROPERTY,
		];

		if (!empty($context['OFFER_IBLOCK_ID']))
		{
			$iblockMap[$context['OFFER_IBLOCK_ID']] = Export\Entity\Manager::TYPE_IBLOCK_OFFER_PROPERTY;
		}

		$query = Iblock\PropertyTable::getList([
			'filter' => [
				'=IBLOCK_ID' => array_keys($iblockMap),
				$filter,
			],
			'select' => [ 'IBLOCK_ID', 'ID' ],
		]);

		while ($property = $query->fetch())
		{
			$result[] = [
				'TYPE' => $iblockMap[$property['IBLOCK_ID']],
				'FIELD' => $property['ID'],
			];
		}

		return $result;
	}
}