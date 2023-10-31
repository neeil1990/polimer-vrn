<?php

namespace Yandex\Market\Export\Entity\Catalog\PriceMatrix;

use Yandex\Market;
use Bitrix\Main;

class Source extends Market\Export\Entity\Catalog\Price\Source
{
	use Market\Reference\Concerns\HasMessage;

	public function getTitle()
	{
		return self::getMessage('TITLE');
	}

	protected function getFieldsPriceTypes()
	{
		$result = parent::getFieldsPriceTypes();
		$result = array_diff_key($result, [
			'OPTIMAL' => true,
		]);

		foreach ($result as &$priceType)
		{
			$priceType['FILTERABLE'] = false;
		}
		unset($priceType);

		return $result;
	}

	protected function getFieldsPriceColumns()
	{
		$result = parent::getFieldsPriceColumns();
		$result += [
			'QUANTITY_FROM' => [
				'VALUE' => self::getMessage('FIELD_QUANTITY_FROM'),
				'TYPE' => Market\Export\Entity\Data::TYPE_NUMBER,
				'FILTERABLE' => false,
				'SELECTABLE' => true,
			],
		];

		return $result;
	}

	public function getElementListValues($elementList, $parentList, $select, $queryContext, $sourceValues)
	{
		$priceFieldsList = $this->getPriceSelectFields($select);

		if (empty($elementList) || empty($priceFieldsList)) { return []; }
		if (!Main\Loader::includeModule('iblock') || !Main\Loader::includeModule('catalog')) { return []; }

		if ($queryContext['DISCOUNT_CACHE'])
		{
			$this->initializeElementListDiscountCache($elementList, $parentList, $select, $queryContext);
		}

		$result = [];
		$elementIds = array_keys($elementList);
		$priceIdsByType = $this->getTypePriceIdsList(array_keys($priceFieldsList), $queryContext);
		$priceIds = $this->getPriceIdsFromListByType($priceIdsByType);
		$elementListPrices = $this->loadElementListPrices($elementIds, $priceIds, $queryContext);

		foreach ($elementList as $elementId => $element)
		{
			$result[$elementId] = [];

			foreach ($priceFieldsList as $priceType => $fields)
			{
				if (empty($priceIdsByType[$priceType]) || empty($elementListPrices[$elementId])) { continue; }

				if (Market\Export\Entity\Catalog\Provider::useCatalogShortFields())
				{
					$element += $this->extendElementBySiblingSources($sourceValues[$elementId]);
				}

				$elementPrices = [];

				foreach ($elementListPrices[$elementId] as $quantityFrom => $rowPrices)
				{
					$elementPrice = $this->getElementCatalogPrice(
						$element,
						$fields,
						$priceType,
						$queryContext,
						$priceIdsByType[$priceType],
						$rowPrices
					);

					if (empty($elementPrice)) { continue; }

					$elementPrices[$quantityFrom] = $elementPrice;
				}

				if (empty($elementPrices)) { continue; }

				foreach ($fields as $field)
				{
					$values = [];

					foreach ($elementPrices as $elementPrice)
					{
						$values[] = $this->getCatalogPriceFieldValue($elementPrice, $field);
					}

					$result[$elementId][$priceType . '.' . $field] = $values;
				}
			}
		}

		if ($queryContext['DISCOUNT_CACHE'])
		{
			$this->releaseElementListDiscountCache($queryContext);
		}

		return $result;
	}

	protected function loadElementListPrices($elementIds, $priceIds, $context)
	{
		if (empty($elementIds) || empty($priceIds)) { return []; }
		if (!Main\Loader::includeModule('catalog')) { return []; }

		$result = [];

		$query = $this->makeElementPricesQuery([
			'@PRODUCT_ID' => $elementIds,
			'@CATALOG_GROUP_ID' => $priceIds,
		], [
			'ID',
			'PRODUCT_ID',
			'CATALOG_GROUP_ID',
			'PRICE',
			'CURRENCY',
			'QUANTITY_FROM',
			'QUANTITY_TO',
		]);

		while ($price = $query->fetch())
		{
			$productId = (int)$price['PRODUCT_ID'];
			$priceGroupId = (int)$price['CATALOG_GROUP_ID'];
			$quantityFrom = (float)$price['QUANTITY_FROM'];

			if ($quantityFrom === 0.0)
			{
				$quantityFrom = 1.0;
			}

			$quantityFrom = (string)$quantityFrom;

			if (!isset($result[$productId])) { $result[$productId] = []; }
			if (!isset($result[$productId][$quantityFrom])) { $result[$productId][$quantityFrom] = []; }

			$result[$productId][$quantityFrom][$priceGroupId] = $price;
		}

		return $result;
	}
}
