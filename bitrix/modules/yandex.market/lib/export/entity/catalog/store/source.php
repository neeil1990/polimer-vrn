<?php

namespace Yandex\Market\Export\Entity\Catalog\Store;

use Yandex\Market;
use Bitrix\Main;
use Bitrix\Catalog;

Main\Localization\Loc::loadMessages(__FILE__);

class Source extends Market\Export\Entity\Reference\Source
{
	public function isFilterable()
	{
		return true;
	}

	public function getQueryFilter($filter, $select)
	{
		$isSupportShortSyntax = Market\Export\Entity\Catalog\Provider::useCatalogShortFields();
		$result = [
			'CATALOG' => []
		];

		foreach ($filter as $filterItem)
		{
			if ($isSupportShortSyntax)
			{
				$fieldName = 'STORE_' . $filterItem['FIELD'];
			}
			else
			{
				$fieldName = 'CATALOG_STORE_' . $filterItem['FIELD'];
			}

		    $this->pushQueryFilter($result['CATALOG'], $filterItem['COMPARE'], $fieldName, $filterItem['VALUE']);
		}

		return $result;
	}

	public function getElementListValues($elementList, $parentList, $select, $queryContext, $sourceValues)
	{
		$result = [];

		if (!empty($elementList) && $queryContext['HAS_CATALOG'])
		{
			$prefix = 'AMOUNT_';
			$storeIds = $this->extractStoresFromAmountSelect($select, $prefix);

			if (!empty($storeIds) && Main\Loader::includeModule('catalog'))
			{
				$elementIds = array_keys($elementList);

				$this->loadAmounts($result, $elementIds, $storeIds, $prefix);
			}
		}

		return $result;
	}

	public function getFields(array $context = [])
	{
		$result = [];

		if ($context['HAS_CATALOG'] && Main\Loader::includeModule('catalog'))
		{
			$langPrefix = $this->getLangPrefix();

			$queryStores = \CCatalogStore::GetList(
				[],
				[ 'ACTIVE' => 'Y' ],
				false,
				false,
				[ 'ID', 'TITLE', 'ADDRESS' ]
			);

			while ($store = $queryStores->Fetch())
			{
				$storeTitle = ($store['TITLE'] ?: $store['ADDRESS'] ?: $store['ID']);

				$result['AMOUNT_' . $store['ID']] = [
					'ID' => 'AMOUNT_' . $store['ID'],
					'VALUE' => Market\Config::getLang($langPrefix . 'FIELD_AMOUNT', [ '#STORE_NAME#' => $storeTitle ]),
					'TYPE' => Market\Export\Entity\Data::TYPE_NUMBER,
					'FILTERABLE' => true,
					'SELECTABLE' => true,
				];
			}
		}

		return $result;
	}

	protected function getLangPrefix()
	{
		return 'CATALOG_STORE_';
	}

	protected function extractStoresFromAmountSelect($select, $prefix)
	{
		$prefixLength = null;
		$result = [];

		foreach ($select as $field)
		{
			if (Market\Data\TextString::getPosition($field, $prefix) === 0)
			{
				if ($prefixLength === null)
				{
					$prefixLength = Market\Data\TextString::getLength($prefix);
				}

				$result[] = (int)Market\Data\TextString::getSubstring($field, $prefixLength);
			}
		}

		return $result;
	}

	protected function loadAmounts(&$result, $elementIds, $storeIds, $prefix)
	{
		$query = \CCatalogStoreProduct::GetList(
			[],
			[ '@PRODUCT_ID' => $elementIds, '=STORE_ID' => $storeIds ],
			false,
			false,
			[ 'PRODUCT_ID', 'STORE_ID', 'AMOUNT' ]
		);

		while ($row = $query->Fetch())
		{
			$productId = (int)$row['PRODUCT_ID'];
			$storeId = (int)$row['STORE_ID'];

			if (!isset($result[$productId])) { $result[$productId] = []; }

			$result[$productId][$prefix . $storeId] = (float)$row['AMOUNT'];
		}
	}
}