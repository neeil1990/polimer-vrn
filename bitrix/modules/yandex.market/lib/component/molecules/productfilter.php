<?php
namespace Yandex\Market\Component\Molecules;

use Bitrix\Main;
use Yandex\Market;
use Yandex\Market\Export;
use Yandex\Market\Reference;

class ProductFilter
{
	use Reference\Concerns\HasMessage;

	private $productFields;

	public function __construct(array $productFields)
	{
		$this->productFields = $productFields;
	}

	public function sanitizeIblock(array $request, array $fields, array $setupIblockList, array $iblockFieldMap = [])
	{
		$result = $request;

		foreach ($this->productFields as $productFieldKey)
		{
			if (!isset($fields[$productFieldKey])) { continue; }

			if (isset($iblockFieldMap[$productFieldKey]))
			{
				$iblockFieldKey = $iblockFieldMap[$productFieldKey];
				$giftIblockId = isset($result[$iblockFieldKey]) ? (int)$result[$iblockFieldKey] : null;

				if ($giftIblockId === null || $giftIblockId <= 0)
				{
					$giftIblockId = (int)reset($setupIblockList);
				}

				$iblockIdList = $giftIblockId > 0 ? [ $giftIblockId ] : [];
			}
			else
			{
				$iblockIdList = $setupIblockList;
			}

			$iblockIdMap = array_flip($iblockIdList);
			$usedIblockMap = [];
			$result[$productFieldKey] = isset($request[$productFieldKey]) ? (array)$request[$productFieldKey] : [];

			foreach ($result[$productFieldKey] as $collectionProductKey => $collectionProduct)
			{
				$iblockId = isset($collectionProduct['IBLOCK_ID']) ? (int)$collectionProduct['IBLOCK_ID'] : null;

				if ($iblockId > 0 && isset($iblockIdMap[$iblockId]))
				{
					$usedIblockMap[$iblockId] = true;
				}
				else
				{
					unset($result[$productFieldKey][$collectionProductKey]);
				}
			}

			foreach ($iblockIdList as $iblockId)
			{
				if ($iblockId > 0 && !isset($usedIblockMap[$iblockId]))
				{
					$result[$productFieldKey][] = [
						'IBLOCK_ID' => $iblockId
					];
				}
			}
		}

		return $result;
	}

	public function sanitizeFilter(array $request, array $fields)
	{
		foreach ($this->productFields as $productFieldKey)
		{
			if (
				isset($fields[$productFieldKey], $request[$productFieldKey])
				&& is_array($request[$productFieldKey])
			)
			{
				foreach ($request[$productFieldKey] as &$productData)
				{
					if (!isset($productData['FILTER']))
					{
						$productData['FILTER'] = [];
					}
				}
				unset($productData);
			}
		}

		return $request;
	}

	public function validate(Main\Entity\Result $result, array $data, array $fields)
	{
		foreach ($this->productFields as $productFieldKey)
		{
			if (!isset($fields[$productFieldKey])) { continue; }

			$field = $fields[$productFieldKey];
			$hasProductFilter = false;

			if (!empty($data[$productFieldKey]))
			{
				foreach ($data[$productFieldKey] as $promoProduct)
				{
					if (empty($promoProduct['FILTER'])) { continue; }

					foreach ($promoProduct['FILTER'] as $filter)
					{
						$hasValidCondition = false;
						$hasProductFilter = true;

						if (!empty($filter['FILTER_CONDITION']))
						{
							foreach ($filter['FILTER_CONDITION'] as $filterCondition)
							{
								if (Export\FilterCondition\Table::isValidData($filterCondition))
								{
									$hasValidCondition = true;
									break;
								}
							}
						}

						if (!$hasValidCondition)
						{
							$result->addError(new Market\Error\EntityError(self::getMessage('ERROR_CONDITION_EMPTY', [
								'#FIELD_NAME#' => $field['LIST_COLUMN_LABEL'],
							])));
							break 2;
						}
					}
				}
			}

			if (!$hasProductFilter)
			{
				$result->addError(new Market\Error\EntityError(self::getMessage('ERROR_FILTER_EMPTY', [
					'#FIELD_NAME#' => $field['LIST_COLUMN_LABEL'],
				])));
			}
		}
	}

	public function extend(array $data)
	{
		$result = $data;

		foreach ($this->productFields as $productFieldKey)
		{
			if (empty($result[$productFieldKey])) { continue; }

			foreach ($result[$productFieldKey] as &$productFilter)
			{
				$productFilter['CONTEXT'] = Market\Export\Entity\Iblock\Provider::getContext($productFilter['IBLOCK_ID']);
			}
			unset($productFilter);
		}

		return $result;
	}
}