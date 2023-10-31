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

	public function getWarehouseDefaultField()
	{
		return 'ID';
	}

	public function getOutletDefaultField()
	{
		return 'ID';
	}

	public function existsStores($field)
	{
		$result = [];

		$query = Catalog\StoreTable::getList([
			'filter' => [ '=ACTIVE' => 'Y' ],
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

		return $result;
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

	public function findStores($field, $value)
	{
		$result = [];

		if ($field === 'ID')
		{
			$result[] = (int)$value;
		}
		else
		{
			$query = Catalog\StoreTable::getList([
				'filter' => [ '=' . $field => $value ],
				'select' => [ 'ID' ],
			]);

			while ($row = $query->fetch())
			{
				$result[] = (int)$row['ID'];
			}
		}

		return $result;
	}

	public function getBasketData($productIds, $quantities = null, array $context = [])
	{
		$stores = isset($context['STORES']) ? (array)$context['STORES'] : [];
		$productIds = $this->filterBasketProducts($productIds, $context);

		$amounts = $this->getAmounts($stores, $productIds);
		$amounts = $this->fillMissingAmounts($amounts, $productIds);

		return $this->makeBasketData($amounts);
	}

	protected function filterBasketProducts($productIds, array $context)
	{
		if (!empty($context['TRACE'])) { return $productIds; }

		$result = [];

		foreach (array_chunk($productIds, 500) as $chunkIds)
		{
			$query = $this->queryTraceableProducts($chunkIds, [ 'ID' ]);

			while ($row = $query->fetch())
			{
				$result[] = (int)$row['ID'];
			}
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

	public function getChanged($stores, Main\Type\DateTime $date = null, $offset = null, $limit = 500)
	{
		$result = [];
		$order = [];
		$filter = [
			'=TYPE' => [
				Catalog\ProductTable::TYPE_PRODUCT,
				Catalog\ProductTable::TYPE_SET,
				Catalog\ProductTable::TYPE_OFFER,
			],
		];

		if ($date !== null)
		{
			$order = [ 'TIMESTAMP_X' => 'ASC' ];
			$filter['>=TIMESTAMP_X'] = $date;
		}

		$query = Catalog\ProductTable::getList([
			'filter' => $filter,
			'offset' => (int)$offset,
			'limit' => (int)$limit,
			'select' => [ 'ID' ],
			'order' => $order,
		]);

		while ($row = $query->fetch())
		{
			$result[] = $row['ID'];
		}

		return $result;
	}

	public function getAmounts($stores, $productIds)
	{
		list($productFields, $storeIds) = $this->splitAmountStores($stores);
		$needLimitByTotalQuantity = false;
		$isReservesIncluded = false;
		$quantityChain = [];

		if (!empty($productFields))
		{
			$isReservesIncluded = true;
			$quantityChain[] = $this->getQuantityFromProduct($productIds, $productFields);
		}

		if (!empty($storeIds))
		{
			$needLimitByTotalQuantity = true;
			$quantityChain[] = $this->getQuantityFromStore($storeIds, $productIds);
		}

		$result = $this->mergeQuantityChain($quantityChain);

		if ($needLimitByTotalQuantity)
		{
			$result = $this->applyQuantityLimitByProduct($result, !$isReservesIncluded);
		}

		return $result;
	}

	public function getLimits($stores, $productIds)
	{
		list($productFields, $storeIds) = $this->splitAmountStores($stores);

		if (!empty($productFields) && empty($storeIds)) { return []; } // already applied

		return $this->loadQuantityLimitFromProduct($productIds);
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

		if (!empty($productIds))
		{
			foreach (array_chunk($productIds, 500) as $productIdChunk)
			{
				$query = Catalog\ProductTable::getList([
					'filter' => [ '=ID' => $productIdChunk ],
					'select' => array_merge(
						[ 'ID' ],
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

		if (!empty($storeList) && !empty($productIdList))
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

				foreach ($amountList as $productId => $quantity)
				{
					$result[] = [
						'ID' => $productId,
						'QUANTITY' => $quantity,
					];
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

	protected function applyQuantityLimitByProduct(array $amounts, $includeReserves = false)
	{
		if ($includeReserves && !$this->isCatalogReservationEnabled()) { return $amounts; }

		$productIds = array_column($amounts, 'ID');
		$limits = $this->loadQuantityLimitFromProduct($productIds, $includeReserves);

		foreach ($amounts as &$amount)
		{
			if (!isset($limits[$amount['ID']])) { continue; }

			$limit = $limits[$amount['ID']];

			if (isset($amount['QUANTITY_LIST']))
			{
				$limitTypes = [
					Market\Data\Trading\Stocks::TYPE_FIT => true,
					Market\Data\Trading\Stocks::TYPE_AVAILABLE => true,
				];

				foreach ($amount['QUANTITY_LIST'] as $type => $quantity)
				{
					if (!isset($limitTypes[$type])) { continue; }
					if ($quantity <= $limit) { continue; }

					$amount['QUANTITY_LIST'][$type] = $limit;
				}
			}

			if (isset($amount['QUANTITY']) && $amount['QUANTITY'] > $limit)
			{
				$amount['QUANTITY'] = $limit;
			}
		}
		unset($amount);

		return $amounts;
	}

	protected function isCatalogReservationEnabled()
	{
		return Main\Config\Option::get('catalog', 'enable_reservation') !== 'N';
	}

	protected function loadQuantityLimitFromProduct(array $productIds, $includeReserves = false)
	{
		$result = [];

		foreach (array_chunk($productIds, 500) as $chunkIds)
		{
			$select = [ 'ID', 'QUANTITY' ];

			if ($includeReserves)
			{
				$select[] = 'QUANTITY_RESERVED';
			}

			$query = $this->queryTraceableProducts($chunkIds, $select);

			while ($row = $query->fetch())
			{
				$quantity = $row['QUANTITY'];

				if ($includeReserves)
				{
					$quantity += $row['QUANTITY_RESERVED'];
				}

				$result[$row['ID']] = $quantity;
			}
		}

		return $result;
	}

	protected function queryTraceableProducts(array $productIds, array $select)
	{
		$traceByDefault = (Main\Config\Option::get('catalog', 'default_quantity_trace') === 'Y');
		$canBuyZeroByDefault = (Main\Config\Option::get('catalog', 'default_can_buy_zero') === 'Y');

		return Catalog\ProductTable::getList([
			'filter' => [
				'=ID' => $productIds,
				'=QUANTITY_TRACE' => $traceByDefault
					? [ Catalog\ProductTable::STATUS_DEFAULT, Catalog\ProductTable::STATUS_YES ]
					: Catalog\ProductTable::STATUS_YES,
				'=CAN_BUY_ZERO' => $canBuyZeroByDefault
					? Catalog\ProductTable::STATUS_NO
					: [ Catalog\ProductTable::STATUS_DEFAULT, Catalog\ProductTable::STATUS_NO ],
			],
			'select' => $select,
		]);
	}

	protected function fillMissingAmounts(array $amounts, $productIds)
	{
		$existsIds = array_column($amounts, 'ID', 'ID');
		$timestamp = new Main\Type\DateTime();

		foreach ($productIds as $productId)
		{
			if (isset($existsIds[$productId])) { continue; }

			$amounts[] = [
				'ID' => $productId,
				'TIMESTAMP_X' => $timestamp,
				'QUANTITY' => 0,
			];
		}

		return $amounts;
	}
}