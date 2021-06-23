<?php

namespace Yandex\Market\Trading\Entity\Common;

use Yandex\Market;
use Bitrix\Main;
use Bitrix\Catalog;

class Store extends Market\Trading\Entity\Reference\Store
{
	use Market\Reference\Concerns\HasLang;

	const PRODUCT_FIELD_QUANTITY = 'CATALOG_QUANTITY';
	const PRODUCT_FIELD_QUANTITY_RESERVED = 'CATALOG_QUANTITY_RESERVED';

	/** @var Environment */
	protected $environment;

	protected static function includeMessages()
	{
		Main\Localization\Loc::loadMessages(__FILE__);
	}

	public function __construct(Environment $environment)
	{
		parent::__construct($environment);
	}

	public function getEnum($siteId = null)
	{
		return array_merge(
			$this->getCatalogQuantityEnum(),
			$this->getCatalogStoreEnum($siteId)
		);
	}

	public function getDefaults()
	{
		return [
			static::PRODUCT_FIELD_QUANTITY,
		];
	}

	protected function getCatalogQuantityEnum()
	{
		return [
			[
				'ID' => static::PRODUCT_FIELD_QUANTITY,
				'VALUE' => static::getLang('TRADING_ENTITY_COMMON_STORE_CATALOG_QUANTITY', null, static::PRODUCT_FIELD_QUANTITY),
			],
			[
				'ID' => static::PRODUCT_FIELD_QUANTITY_RESERVED,
				'VALUE' => static::getLang('TRADING_ENTITY_COMMON_STORE_CATALOG_QUANTITY_RESERVED', null, static::PRODUCT_FIELD_QUANTITY_RESERVED),
			],
		];
	}

	protected function getCatalogStoreEnum($siteId)
	{
		$result = [];
		$filter = [ '=ACTIVE' => 'Y' ];

		if ($siteId !== null)
		{
			$filter[] = [
				'LOGIC' => 'OR',
				[ '=SITE_ID' => $siteId ],
				[ 'SITE_ID' => false ],
			];
		}

		$query = Catalog\StoreTable::getList([
			'filter' => $filter,
			'select' => [ 'ID', 'TITLE', 'ADDRESS' ]
		]);

		while ($row = $query->fetch())
		{
			$storeTitle = (string)$row['TITLE'] !== '' ? $row['TITLE'] : $row['ADDRESS'];

			$result[] = [
				'ID' => $row['ID'],
				'VALUE' => '[' . $row['ID'] . '] ' . $storeTitle,
			];
		}

		return $result;
	}

	public function getFieldEnum($siteId = null)
	{
		return array_merge(
			$this->getCatalogStoreCommonFieldEnum(),
			$this->getCatalogStoreUserFieldEnum()
		);
	}

	protected function getCatalogStoreCommonFieldEnum()
	{
		$result = [];
		$usedFields = [
			'ID',
			'CODE',
			'XML_ID',
		];

		foreach (Catalog\StoreTable::getEntity()->getScalarFields() as $tableField)
		{
			$fieldName = $tableField->getName();

			if (!in_array($fieldName, $usedFields, true)) { continue; }

			$result[] = [
				'ID' => $fieldName,
				'VALUE' => $tableField->getTitle(),
			];
		}

		return $result;
	}

	protected function getCatalogStoreUserFieldEnum()
	{
		global $USER_FIELD_MANAGER;

		$ufId = Catalog\StoreTable::getUfId();
		$result = [];

		foreach ($USER_FIELD_MANAGER->getUserFields($ufId, 0, LANGUAGE_ID) as $fieldName => $userField)
		{
			$title = $userField['EDIT_FORM_LABEL']
				?: $userField['LIST_COLUMN_LABEL']
				?: $userField['LIST_FILTER_LABEL'];

			$result[] = [
				'ID' => $fieldName,
				'VALUE' => sprintf('[%s] %s', $fieldName, $title),
				'GROUP' => static::getLang('TRADING_ENTITY_COMMON_STORE_FIELD_USER_FIELD_GROUP'),
			];
		}

		return $result;
	}

	public function getOutletDefaultField()
	{
		return 'ID';
	}

	public function mapStores($field, $ids)
	{
		$result = [];
		$ids = (array)$ids;

		Main\Type\Collection::normalizeArrayValuesByInt($ids, false);

		if ($field === 'ID')
		{
			$result = array_combine($ids, $ids);
		}
		else if (!empty($ids))
		{
			$query = Catalog\StoreTable::getList([
				'filter' => [ '=ID' => $ids, '=ACTIVE' => 'Y' ],
				'select' => [ 'ID', $field ],
			]);

			while ($row = $query->fetch())
			{
				if (
					!isset($row[$field])
					|| !is_scalar($row[$field])
					|| (string)$row[$field] === ''
				)
				{ continue; }

				$result[$row['ID']] = $row[$field];
			}
		}

		return $result;
	}

	public function findStore($field, $value)
	{
		$result = null;

		if ($field === 'ID')
		{
			$result = (int)$value;
		}
		else
		{
			$query = Catalog\StoreTable::getList([
				'filter' => [ '=' . $field => $value ],
				'select' => [ 'ID' ],
				'limit' => 1,
			]);

			if ($row = $query->fetch())
			{
				$result = (int)$row['ID'];
			}
		}

		return $result;
	}

	public function getBasketData($productIds, $quantities = null, array $context = [])
	{
		$useTrace = !empty($context['TRACE']);
		$stores = !empty($context['STORES']) ? (array)$context['STORES'] : null;

		if ($stores !== null && $useTrace)
		{
			$amounts = $this->getAmounts($stores, $productIds);
			$result = $this->makeBasketData($amounts);
		}
		else
		{
			$result = [];
		}

		return $result;
	}

	protected function makeBasketData($amounts)
	{
		$result = [];

		foreach ($amounts as $amount)
		{
			$quantity = null;

			if (isset($amount['QUANTITY']))
			{
				$quantity = $amount['QUANTITY'];
			}
			else if (isset($amount['QUANTITY_LIST'][Market\Data\Trading\Stocks::TYPE_FIT]))
			{
				$quantity = $amount['QUANTITY_LIST'][Market\Data\Trading\Stocks::TYPE_FIT];
			}

			if ($quantity !== null)
			{
				$result[$amount['ID']] = [
					'AVAILABLE_QUANTITY' => $quantity
				];
			}
		}

		return $result;
	}

	public function getAmounts($stores, $productIds)
	{
		list($productFields, $storeIds) = $this->splitAmountStores($stores);
		$quantityChain = [];

		if (!empty($productFields))
		{
			$quantityChain[] = $this->getQuantityFromProduct($productIds, $productFields);
		}

		if (!empty($storeIds))
		{
			$quantityChain[] = $this->getQuantityFromStore($storeIds, $productIds);
		}

		return $this->mergeQuantityChain($quantityChain);
	}

	protected function splitAmountStores($stores)
	{
		$productFieldsMap = $this->getProductFieldsMap();
		$productFields = [];
		$storeIds = [];

		foreach ($stores as $store)
		{
			if (isset($productFieldsMap[$store]))
			{
				$productFields[] = $productFieldsMap[$store];
			}
			else
			{
				$storeIds[] = $store;
			}
		}

		return [ $productFields, $storeIds ];
	}

	protected function getProductFieldsMap()
	{
		return [
			static::PRODUCT_FIELD_QUANTITY => 'QUANTITY',
			static::PRODUCT_FIELD_QUANTITY_RESERVED => 'QUANTITY_RESERVED',
		];
	}

	protected function getQuantityFromProduct($productIds, array $fields = [ 'QUANTITY' ])
	{
		$result = [];

		if (!empty($productIds) && Main\Loader::includeModule('catalog'))
		{
			foreach (array_chunk($productIds, 500) as $productIdChunk)
			{
				$query = Catalog\ProductTable::getList([
					'filter' => [ '=ID' => $productIdChunk ],
					'select' => array_merge(
						[ 'ID', 'TIMESTAMP_X' ],
						$fields
					)
				]);

				while ($row = $query->fetch())
				{
					$quantity = 0;

					foreach ($fields as $field)
					{
						$quantity += $row[$field];
					}

					$result[] = [
						'ID' => $row['ID'],
						'TIMESTAMP_X' => $row['TIMESTAMP_X'],
						'QUANTITY' => $quantity,
					];
				}
			}
		}

		return $result;
	}

	protected function getQuantityFromStore($storeList, $productIdList)
	{
		$result = [];
		$storeList = (array)$storeList;

		Main\Type\Collection::normalizeArrayValuesByInt($storeList);

		if (
			!empty($storeList)
			&& !empty($productIdList)
			&& Main\Loader::includeModule('iblock')
			&& Main\Loader::includeModule('catalog')
		)
		{
			foreach (array_chunk($productIdList, 500) as $productIdChunk)
			{
				$amountList = [];

				$query = \CCatalogStoreProduct::GetList(
					[],
					[ '=STORE_ID' => $storeList, '=PRODUCT_ID' => $productIdChunk ],
					false,
					false,
					[ 'PRODUCT_ID', 'AMOUNT' ]
				);

				while ($row = $query->fetch())
				{
					if (!isset($amountList[$row['PRODUCT_ID']]))
					{
						$amountList[$row['PRODUCT_ID']] = 0;
					}

					$amountList[$row['PRODUCT_ID']] += $row['AMOUNT'];
				}

				if (!empty($amountList))
				{
					$queryProduct = Catalog\ProductTable::getList([
						'filter' => [ '=ID' => array_keys($amountList) ],
						'select' => [ 'ID', 'TIMESTAMP_X' ]
					]);

					while ($product = $queryProduct->fetch())
					{
						$result[] = [
							'ID' => $product['ID'],
							'TIMESTAMP_X' => $product['TIMESTAMP_X'],
							'QUANTITY' => $amountList[$product['ID']]
						];
					}
				}
			}
		}

		return $result;
	}

	protected function mergeQuantityChain($chain)
	{
		$result = [];

		if (count($chain) === 1)
		{
			$result = reset($chain);
		}
		else
		{
			$productMap = [];

			foreach ($chain as $quantityList)
			{
				foreach ($quantityList as $productData)
				{
					$productId = $productData['ID'];

					if (!isset($productMap[$productId]))
					{
						$resultLength = array_push($result, $productData);
						$productMap[$productId] = $resultLength - 1;
					}
					else
					{
						$resultIndex = $productMap[$productId];
						$resultData = &$result[$resultIndex];
						$hasResultDataQuantityList = isset($resultData['QUANTITY_LIST']);
						$hasProductDataQuantityList = isset($productData['QUANTITY_LIST']);

						if ($hasResultDataQuantityList && $hasProductDataQuantityList)
						{
							foreach ($productData['QUANTITY_LIST'] as $quantityType => $quantity)
							{
								if (!isset($resultData['QUANTITY_LIST'][$quantityType]))
								{
									$resultData['QUANTITY_LIST'][$quantityType] = $quantity;
								}
								else
								{
									$resultData['QUANTITY_LIST'][$quantityType] += $quantity;
								}
							}
						}
						else if ($hasResultDataQuantityList || $hasProductDataQuantityList)
						{
							$mergeData = $hasResultDataQuantityList ? $resultData : $productData;
							$mergeQuantity = $hasResultDataQuantityList ? $productData['QUANTITY'] : $resultData['QUANTITY'];
							$mergeTypes = [
								Market\Data\Trading\Stocks::TYPE_FIT => true,
								Market\Data\Trading\Stocks::TYPE_AVAILABLE => true,
							];

							foreach ($mergeData['QUANTITY_LIST'] as $quantityType => $quantity)
							{
								if (isset($mergeTypes[$quantityType]))
								{
									$mergeData['QUANTITY_LIST'][$quantityType] += $mergeQuantity;
								}
							}

							$resultData = $mergeData;
						}
						else
						{
							$resultData['QUANTITY'] += $productData['QUANTITY'];
						}

						unset($resultData);
					}
				}
			}
		}

		return $result;
	}
}