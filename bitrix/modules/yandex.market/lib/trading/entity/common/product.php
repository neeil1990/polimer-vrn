<?php

namespace Yandex\Market\Trading\Entity\Common;

use Yandex\Market;
use Yandex\Market\Utils\ArrayHelper;
use Bitrix\Main;
use Bitrix\Catalog;
use Bitrix\Iblock;
use Bitrix\Highloadblock;

class Product extends Market\Trading\Entity\Reference\Product
{
	use Market\Reference\Concerns\HasLang;
	use Market\Reference\Concerns\HasOnce;

	protected $propertyDataCache = [];
	protected $accessAlreadyWait = [];

	protected static function includeMessages()
	{
		Main\Localization\Loc::loadMessages(__FILE__);
	}

	public function getSkuMap($productIds, $skuMap)
	{
		$result = [];
		$iblockProductIds = $this->splitProductIdsByIblock($productIds);

		foreach ($skuMap as $skuMapItem)
		{
			foreach ($iblockProductIds as $iblockId => &$leftProductIds)
			{
				if (empty($leftProductIds)) { break; }

				if ((int)$skuMapItem['IBLOCK'] === $iblockId)
				{
					$foundOffers = $this->queryOfferMap($skuMapItem, $leftProductIds, '=ID');
					$leftProductIds = array_diff($leftProductIds, array_keys($foundOffers));

					if ($this->useAutoSelectProductOffer())
					{
						$foundOffers = $this->unsetSkuProducts($skuMapItem, $foundOffers);
					}

					$result += $foundOffers;
				}
				else if (
					$this->useAutoSelectProductOffer()
					&& $this->skuOfferIblockId($skuMapItem['IBLOCK']) === $iblockId
				)
				{
					$offerProductData = \CCatalogSku::getProductList($leftProductIds, $iblockId);
					$offerProductMap = ArrayHelper::column($offerProductData, 'ID');
					$parentProductIds = array_unique($offerProductMap);

					$foundParents = $this->queryOfferMap($skuMapItem, $parentProductIds, '=ID');
					$foundParents = $this->autoSelectProductsOffer($skuMapItem, $foundParents);

					$foundOffers = array_intersect_key($foundParents, array_flip($leftProductIds));

					$leftProductIds = array_diff($leftProductIds, array_keys($foundOffers));
					$result += $foundOffers;
				}
			}
			unset($leftProductIds);
		}

		return $result;
	}

	public function getOfferMap($offerIds, $skuMap)
	{
		$result = [];
		$leftOfferIds = $offerIds;

		foreach ($skuMap as $skuMapItem)
		{
			if (empty($leftOfferIds)) { break; }

			$skuField = (string)$skuMapItem['FIELD'];
			$propertyData = $this->getFieldPropertyData($skuField);
			$propertyType = ($propertyData !== false ? $propertyData['PROPERTY_TYPE'] : null);

			if ($propertyType === Iblock\PropertyTable::TYPE_LIST)
			{
				$filterKey = '=' . $skuField . '_VALUE';
			}
			else
			{
				$filterKey = '=' . $skuField;
			}

			$foundOffers = $this->queryOfferMap($skuMapItem, $leftOfferIds, $filterKey);

			if ($this->useAutoSelectProductOffer())
			{
				$foundOffers = $this->autoSelectProductsOffer($skuMapItem, $foundOffers);
			}

			$result += array_flip($foundOffers);
			$leftOfferIds = array_diff($leftOfferIds, $foundOffers);
		}

		return $result;
	}

	protected function queryOfferMap($skuMapItem, $primaries, $filterKey)
	{
		$iblockId = (int)$skuMapItem['IBLOCK'];
		$skuField = (string)$skuMapItem['FIELD'];
		$skuFieldValueKey = (
			Market\Data\TextString::getPosition($skuField, 'PROPERTY_') === 0
				? $skuField . '_VALUE'
				: $skuField
		);
		$filterValueKey = ltrim($filterKey, '=');
		$filterValueKey = (
			Market\Data\TextString::getPosition($filterValueKey, 'PROPERTY_') === 0
			&& Market\Data\TextString::getPosition($filterValueKey, '_VALUE') === false
				? $filterValueKey . '_VALUE'
				: $filterValueKey
		);
		$iblockFilter = [ '=ACTIVE' => 'Y', '=ACTIVE_DATE' => 'Y' ];
		$foundOffers = [];

		if ($iblockId > 0)
		{
			$iblockFilter['IBLOCK_ID'] = $iblockId;
		}

		foreach (array_chunk($primaries, 500) as $primariesChunk)
		{
			$primariesMap = array_flip($primariesChunk);
			$iblockFilter[$filterKey] = $primariesChunk;

			$query = \CIBlockElement::GetList(
				[],
				$iblockFilter,
				false,
				false,
				[ 'IBLOCK_ID', 'ID', $skuField ]
			);

			while ($row = $query->Fetch())
			{
				$offerId = isset($row[$skuFieldValueKey]) ? (string)$row[$skuFieldValueKey] : '';
				$filterValue = isset($row[$filterValueKey]) ? $row[$filterValueKey] : null;

				if ($offerId === '' || !isset($primariesMap[$filterValue])) { continue; }

				$foundOffers[$row['ID']] = $offerId;
			}
		}

		return $foundOffers;
	}

	protected function splitProductIdsByIblock($productIds)
	{
		$result = [];

		foreach (array_chunk($productIds, 500) as $productChunk)
		{
			$query = Iblock\ElementTable::getList([
				'filter' => [ '=ID' => $productChunk ],
				'select' => [ 'IBLOCK_ID', 'ID' ],
			]);

			while ($row = $query->fetch())
			{
				$iblockId = (int)$row['IBLOCK_ID'];
				$id = (int)$row['ID'];

				if (!isset($result[$iblockId]))
				{
					$result[$iblockId] = [];
				}

				$result[$iblockId][] = $id;
			}
		}

		return $result;
	}

	protected function unsetSkuProducts($skuMapItem, array $offerMap)
	{
		if (empty($offerMap) || !$this->isSkuIblock($skuMapItem['IBLOCK'])) { return $offerMap; }

		foreach (array_chunk($offerMap, 500, true) as $offerMapChunk)
		{
			$querySkuProducts = Catalog\ProductTable::getList([
				'filter' => [
					'=ID' => array_keys($offerMapChunk),
					'=TYPE' => Catalog\ProductTable::TYPE_SKU,
				],
				'select' => [ 'ID' ],
			]);

			while ($row = $querySkuProducts->fetchAll())
			{
				if (!isset($offerMap[$row['ID']])) { continue; }

				unset($offerMap[$row['ID']]);
			}
		}

		return $offerMap;
	}

	protected function autoSelectProductsOffer($skuMapItem, array $offerMap)
	{
		if (empty($offerMap) || !$this->isSkuIblock($skuMapItem['IBLOCK'])) { return $offerMap; }

		foreach (array_chunk($offerMap, 500, true) as $offerMapChunk)
		{
			$querySkuProducts = Catalog\ProductTable::getList([
				'filter' => [
					'=ID' => array_keys($offerMapChunk),
					'=TYPE' => Catalog\ProductTable::TYPE_SKU,
				],
				'select' => [ 'ID' ],
			]);
			$skuProductIds = array_column($querySkuProducts->fetchAll(), 'ID');

			if (empty($skuProductIds)) { continue; }

			$availableKey = Market\Export\Entity\Catalog\Provider::useCatalogShortFields()
				? 'AVAILABLE'
				: 'CATALOG_AVAILABLE';

			$skuOffers = \CCatalogSku::getOffersList(
				$skuProductIds,
				$skuMapItem['IBLOCK'],
				[],
				[ 'ID' ],
				[],
				[],
				[ 'ACTIVE' => 'DESC', $availableKey => 'DESC' ]
			);

			foreach ($skuOffers as $productId => $productOffers)
			{
				if (!isset($offerMap[$productId]) || empty($productOffers)) { continue; }

				$firstOffer = reset($productOffers);

				$offerMap[$firstOffer['ID']] = $offerMap[$productId];
				unset($offerMap[$productId]);
			}
		}

		return $offerMap;
	}

	protected function useAutoSelectProductOffer()
	{
		return (Market\Config::getOption('trading_auto_product_offer', 'Y') === 'Y');
	}

	protected function isSkuIblock($iblockId)
	{
		$iblockCatalog = \CCatalogSku::GetInfoByIBlock($iblockId);

		return $iblockCatalog && in_array($iblockCatalog['CATALOG_TYPE'], [
			\CCatalogSku::TYPE_PRODUCT,
			\CCatalogSku::TYPE_FULL,
		], true);
	}

	protected function skuOfferIblockId($iblockId)
	{
		$iblockCatalog = \CCatalogSku::GetInfoByIBlock($iblockId);

		if (!$iblockCatalog) { return null; }

		return (
			(int)$iblockId === (int)$iblockCatalog['PRODUCT_IBLOCK_ID']
			&& (int)$iblockId !== (int)$iblockCatalog['IBLOCK_ID']
				? (int)$iblockCatalog['IBLOCK_ID']
				: null
		);
	}

	protected function getFieldPropertyData($field)
	{
		$result = false;

		if (preg_match('/^PROPERTY_(\d+)$/', $field, $matches))
		{
			$propertyId = (int)$matches[1];

			if (isset($this->propertyDataCache[$propertyId]))
			{
				$result = $this->propertyDataCache[$propertyId];
			}
			else
			{
				$query = Iblock\PropertyTable::getList([
					'filter' => [ '=ID' => $propertyId ],
					'select' => [ 'PROPERTY_TYPE', 'USER_TYPE', 'USER_TYPE_SETTINGS' ]
				]);

				while ($row = $query->fetch())
				{
					$result = $row;
				}

				$this->propertyDataCache[$propertyId] = $result;
			}
		}

		return $result;
	}

	public function debugBasketData($productIds)
	{
		$products = $this->loadProducts($productIds, [ 'QUANTITY', 'QUANTITY_TRACE', 'CAN_BUY_ZERO', 'TIMESTAMP_X' ]);
		$result = [];

		foreach ($productIds as $productId)
		{
			if (!isset($products[$productId])) { continue; }

			$product = $products[$productId];
			$data = [
				'TIMESTAMP_X' => (string)$product['TIMESTAMP_X'],
			];

			if ($product['QUANTITY_TRACE'] === 'Y' && $product['CAN_BUY_ZERO'] === 'N')
			{
				$data['QUANTITY'] = $product['QUANTITY'];
			}

			$result[$productId] = $data;
		}

		return $result;
	}

	public function getBasketData($productIds, $quantities = null, array $context = [])
	{
		$elements = $this->loadElements($productIds, [ 'XML_ID', 'IBLOCK_ID', 'IBLOCK_XML_ID' => 'IBLOCK.XML_ID' ]);
		$products = $this->loadProducts($productIds, [ 'TYPE', 'QUANTITY', 'QUANTITY_TRACE', 'CAN_BUY_ZERO' ]);
		$offers = array_filter($products, static function($product) {
			return (int)$product['TYPE'] === Catalog\ProductTable::TYPE_OFFER;
		});
		$offerElements = array_intersect_key($elements, $offers);
		$offerParentMap = $this->loadOfferParentMap($offerElements);
		$offerProperties = $this->loadOfferProperties($offerElements);
		$parentIds = array_unique($offerParentMap);
		$parents = $this->loadElements($parentIds, [ 'XML_ID' ]);
		$result = [];

		foreach ($productIds as $productId)
		{
			$element = isset($elements[$productId]) ? $elements[$productId] : null;
			$properties = isset($offerProperties[$productId]) ? $offerProperties[$productId] : null;
			$product = isset($products[$productId]) ? $products[$productId] : null;
			$parent = null;

			if (isset($offerParentMap[$productId]))
			{
				$parentId = $offerParentMap[$productId];
				$parent = isset($parents[$parentId]) ? $parents[$parentId] : null;
			}

			$validationError = $this->validateElementBasketData($element, $product, $parent, $context);
			$basketData = $this->mergeElementBasketData([
				$this->fillElementProperties($properties),
				$this->fillElementBasketXmlId($element, $parent)
			]);

			if ($validationError !== null)
			{
				$basketData['ERROR'] = $validationError;
			}

			$result[$productId] = $basketData;
		}

		return $result;
	}

	protected function loadElements($productIds, array $select = [])
	{
		$result = [];

		if (empty($productIds)) { return $result; }

		$query = Iblock\ElementTable::getList([
			'filter' => [ '=ID' => $productIds ],
			'select' => array_merge(
				[ 'IBLOCK_ID', 'ID', 'ACTIVE', 'ACTIVE_FROM', 'ACTIVE_TO' ],
				$select
			)
		]);

		while ($row = $query->Fetch())
		{
			$result[$row['ID']] = $row;
		}

		return $result;
	}

	protected function existsElements($productIds, $checkActive = false)
	{
		$elements = $this->loadElements($productIds);
		$productMap = array_flip($productIds);
		$notExistsElements = array_diff_key($productMap, $elements);
		$result = true;

		if (!empty($notExistsElements))
		{
			$result = false;
		}
		else if ($checkActive)
		{
			foreach ($elements as $element)
			{
				if (!$this->isElementActive($element))
				{
					$result = false;
					break;
				}
			}
		}

		return $result;
	}

	protected function loadProducts($productIds, array $select = [])
	{
		$result = [];

		if (empty($productIds)) { return $result; }

		$query = Catalog\ProductTable::getList([
			'filter' => [ '=ID' => $productIds ],
			'select' => array_merge([ 'ID' ], $select)
		]);

		while ($row = $query->fetch())
		{
			$result[$row['ID']] = $row;
		}

		return $result;
	}

	protected function getSetProducts($productId)
	{
		$result = [];
		$allSets = \CCatalogProductSet::getAllSetsByProduct($productId, \CCatalogProductSet::TYPE_SET);

		if (!empty($allSets))
		{
			$firstSet = reset($allSets);

			foreach ($firstSet['ITEMS'] as $setItem)
			{
				$setItemProductId = (int)$setItem['ITEM_ID'];
				$setItemOwnerId = (int)$setItem['OWNER_ID'];

				if ($setItemProductId !== $setItemOwnerId)
				{
					$result[] = $setItemProductId;
				}
			}
		}

		return $result;
	}

	protected function loadOfferParentMap($offers)
	{
		$offersByIblock = $this->groupElementsByIblock($offers);
		$result = [];

		foreach ($offersByIblock as $iblockId => $offerIds)
		{
			$offerProductData = \CCatalogSku::getProductList($offerIds, $iblockId);

			foreach ($offerProductData as $offerId => $productData)
			{
				$result[$offerId] = (int)$productData['ID'];
			}
		}

		return $result;
	}

	protected function loadOfferProperties($elements)
	{
		$result = [];

		if (!$this->isPropertyFeatureEnabled()) { return $result; }

		$elementsByIblock = $this->groupElementsByIblock($elements);

		foreach ($elementsByIblock as $iblockId => $elementIds)
		{
			$propertyIds = $this->getFeatureProperties($iblockId);

			if (empty($propertyIds)) { continue; }

			$iblockCatalog = \CCatalogSku::GetInfoByIBlock($iblockId);

			if (
				empty($iblockCatalog['PRODUCT_IBLOCK_ID'])
				|| $iblockCatalog['CATALOG_TYPE'] !== \CCatalogSku::TYPE_OFFERS
			)
			{
				continue;
			}

			foreach ($elementIds as $elementId)
			{
				$result[$elementId] = \CIBlockPriceTools::GetOfferProperties(
					$elementId,
					$iblockCatalog['PRODUCT_IBLOCK_ID'],
					$propertyIds
				);
			}
		}

		return $result;
	}

	protected function isPropertyFeatureEnabled()
	{
		return (
			class_exists(Catalog\Product\PropertyCatalogFeature::class)
			&& Catalog\Product\PropertyCatalogFeature::isEnabledFeatures()
		);
	}

	protected function getFeatureProperties($iblockId)
	{
		return Catalog\Product\PropertyCatalogFeature::getBasketPropertyCodes($iblockId, [ 'CODE' => 'Y' ]);
	}

	protected function groupElementsByIblock($elements)
	{
		$result = [];

		foreach ($elements as $element)
		{
			$iblockId = (int)$element['IBLOCK_ID'];

			if (!isset($result[$iblockId]))
			{
				$result[$iblockId] = [];
			}

			$result[$iblockId][] = (int)$element['ID'];
		}

		return $result;
	}

	protected function validateElementBasketData($element, $product, $parent, array $context = [])
	{
		$result = null;

		try
		{
			if ($element === null)
			{
				$message = static::getLang('TRADING_ENTITY_COMMON_PRODUCT_BASKET_ERR_NO_IBLOCK_ELEMENT');
				throw new Main\SystemException($message);
			}

			if (!$this->isElementActive($element))
			{
				$message = static::getLang('TRADING_ENTITY_COMMON_PRODUCT_BASKET_ERR_ELEMENT_INACTIVE');
				throw new Main\SystemException($message);
			}

			if (!$this->hasElementAccess($element, $context) && !$this->waitElementAccess($element, $context))
			{
				$message = static::getLang('TRADING_ENTITY_COMMON_PRODUCT_BASKET_ERR_ELEMENT_ACCESS_DENIED');
				throw new Main\SystemException($message);
			}

			if ($product === null)
			{
				$message = static::getLang('TRADING_ENTITY_COMMON_PRODUCT_BASKET_ERR_NO_PRODUCT');
				throw new Main\SystemException($message);
			}

			if (
				$product['CAN_BUY_ZERO'] === Catalog\ProductTable::STATUS_NO
				&& $product['QUANTITY_TRACE'] === Catalog\ProductTable::STATUS_YES
				&& (float)$product['QUANTITY'] <= 0.0
				&& $this->useTraceQuantityValidation()
			)
			{
				$message = static::getLang('TRADING_ENTITY_COMMON_PRODUCT_BASKET_ERR_TRACE_QUANTITY_OUT');
				throw new Main\SystemException($message);
			}

			$productType = (int)$product['TYPE'];

			if (
				($productType === Catalog\ProductTable::TYPE_SKU || $productType === Catalog\ProductTable::TYPE_EMPTY_SKU)
				&& Main\Config\Option::get('catalog', 'show_catalog_tab_with_offers') !== 'Y'
			)
			{
				$message = static::getLang('TRADING_ENTITY_COMMON_PRODUCT_BASKET_ERR_CANNOT_ADD_SKU');
				throw new Main\SystemException($message);
			}

			if ($productType === Catalog\ProductTable::TYPE_OFFER)
			{
				if ($parent === null)
				{
					$message = static::getLang('TRADING_ENTITY_COMMON_PRODUCT_BASKET_ERR_PARENT_NOT_FOUND');
					throw new Main\SystemException($message);
				}

				if (!$this->isElementActive($parent))
				{
					$message = static::getLang('TRADING_ENTITY_COMMON_PRODUCT_BASKET_ERR_PARENT_INACTIVE');
					throw new Main\SystemException($message);
				}
			}

			if ($productType === Catalog\ProductTable::TYPE_SET)
			{
				$setProducts = $this->getSetProducts($product['ID']);

				if (empty($setProducts))
				{
					$message = static::getLang('TRADING_ENTITY_COMMON_PRODUCT_BASKET_ERR_NO_PRODUCT_SET');
					throw new Main\SystemException($message);
				}

				if (!$this->existsElements($setProducts, true))
				{
					$message = static::getLang('TRADING_ENTITY_COMMON_PRODUCT_BASKET_ERR_NO_PRODUCT_SET_ITEMS');
					throw new Main\SystemException($message);
				}
			}
		}
		catch (Main\SystemException $exception)
		{
			$result = new Main\Error($exception->getMessage(), $exception->getCode());
		}

		return $result;
	}

	protected function useTraceQuantityValidation()
	{
		return (Market\Config::getOption('trading_product_validate_quantity', 'Y') === 'Y');
	}

	protected function mergeElementBasketData($dataList)
	{
		$result = array_shift($dataList);
		$multipleFields = [
			'PROPS',
		];

		foreach ($dataList as $data)
		{
			foreach ($multipleFields as $multipleField)
			{
				if (
					isset($data[$multipleField])
					&& array_key_exists($multipleField, $result)
				)
				{
					$result[$multipleField] = array_merge(
						(array)$result[$multipleField],
						(array)$data[$multipleField]
					);
				}
			}

			$result += $data;
		}

		return $result;
	}

	protected function fillElementProperties($properties)
	{
		$result = [];

		if (!empty($properties))
		{
			$result['PROPS'] = (array)$properties;
		}

		return $result;
	}

	protected function fillElementBasketXmlId($element, $parent = null)
	{
		$result = [
			'PROPS' => [],
		];
		$productXmlId = isset($element['XML_ID']) ? (string)$element['XML_ID'] : '';
		$catalogXmlId = isset($element['IBLOCK_XML_ID']) ? (string)$element['IBLOCK_XML_ID'] : '';

		if ($productXmlId !== '')
		{
			if ($parent !== null && Market\Data\TextString::getPosition($productXmlId, '#') === false)
			{
				$productXmlId = $parent['XML_ID'] . '#' . $productXmlId;
			}

			$result['PRODUCT_XML_ID'] = $productXmlId;
			$result['PROPS'][] = [
				'NAME' => 'Product XML_ID',
				'CODE' => 'PRODUCT.XML_ID',
				'VALUE' => $productXmlId,
			];
		}

		if ($catalogXmlId !== '')
		{
			$result['CATALOG_XML_ID'] = $element['IBLOCK_XML_ID'];
			$result['PROPS'][] = [
				'NAME' => 'Catalog XML_ID',
				'CODE' => 'CATALOG.XML_ID',
				'VALUE' => $element['IBLOCK_XML_ID'],
			];
		}

		return $result;
	}

	protected function isElementActive($element)
	{
		$result = true;

		if ($element['ACTIVE'] !== 'Y')
		{
			$result = false;
		}
		else if (
			$element['ACTIVE_FROM'] instanceof Main\Type\Date
			&& $element['ACTIVE_FROM']->getTimestamp() > time()
		)
		{
			$result = false;
		}
		else if (
			$element['ACTIVE_TO'] instanceof Main\Type\Date
			&& $element['ACTIVE_TO']->getTimestamp() < time()
		)
		{
			$result = false;
		}

		return $result;
	}

	protected function hasElementAccess($element, array $context)
	{
		return (
			$this->testElementAccessByQuery($element)
			|| $this->testElementAccessByRights($element, $context)
		);
	}

	protected function testElementAccessByQuery($element)
	{
		if (empty($element['ID'])) { return false; }

		/* \Bitrix\Catalog\Product\CatalogProvider::getElements skip check USER_ID */
		$query = \CIBlockElement::GetList(
			[],
			[
				'ID' => $element['ID'],
				'CHECK_PERMISSIONS' => 'Y',
				'MIN_PERMISSION' => 'R',
			],
			false,
			[ 'nTopCount' => 1 ],
			[ 'ID' ]
		);

		return (bool)$query->Fetch();
	}

	protected function testElementAccessByRights($element, array $context)
	{
		$iblockId = $element['IBLOCK_ID'];
		$userId = isset($context['USER_ID']) ? $context['USER_ID'] : 0;

		if (\CIBlock::GetArrayByID($iblockId, 'RIGHTS_MODE') === 'E')
		{
			$operations = \CIBlockElementRights::GetUserOperations($element['ID'], $userId);
		}
		else
		{
			$level = \CIBlock::GetPermission($iblockId, $userId);
			$operations = \CIBlockRights::LetterToOperations($level);
		}

		return in_array('element_read', $operations, true);
	}

	// wait extended rights parallel recalculation
	protected function waitElementAccess($element, array $context)
	{
		if (
			isset($this->accessAlreadyWait[$element['IBLOCK_ID']])
			|| \CIBlock::GetArrayByID($element['IBLOCK_ID'], 'RIGHTS_MODE') !== 'E'
		)
		{
			return false;
		}

		$result = false;
		$previousRepeat = 0;
		$previousCount = null;

		for ($i = 0; $i < 100; $i++) // 10 seconds
		{
			usleep(100000); // 0.1 second

			if ($this->hasElementAccess($element, $context))
			{
				$result = true;
				break;
			}

			// check recalculation in process

			$count = $this->countElementAccessRights($element);
			$previousRepeat = $previousCount < $count ? 0 : ($previousRepeat + 1);

			if ($count === null || $previousRepeat >= 10)
			{
				$this->accessAlreadyWait[$element['IBLOCK_ID']] = true;
				break;
			}

			$previousCount = $count;
		}

		return $result;
	}

	protected function countElementAccessRights($element)
	{
		if (empty($element['IBLOCK_ID'])) { return null; }

		$result = null;

		$connection = Main\Application::getConnection();
		$query = $connection->query(sprintf(
			'SELECT COUNT(*) `CNT` FROM b_iblock_element_right WHERE `IBLOCK_ID` = %s',
			(int)$element['IBLOCK_ID']
		));

		if ($row = $query->fetch())
		{
			$result = (int)$row['CNT'];
		}

		return $result;
	}

	public function getFieldEnum($iblockId)
	{
		return array_merge(
			$this->getIblockFieldEnum(),
			$this->getIblockPropertyEnum($iblockId)
		);
	}

	protected function getIblockFieldEnum()
	{
		$fields = [
			'ID',
			'CODE',
			'XML_ID',
			'NAME',
		];
		$result = [];

		foreach ($fields as $field)
		{
			$result[] = [
				'ID' => $field,
				'VALUE' => static::getLang('TRADING_ENTITY_COMMON_PRODUCT_FIELD_' . $field, null, $field),
			];
		}

		return $result;
	}

	protected function getIblockPropertyEnum($iblockId)
	{
		$result = [];
		$iblockId = (int)$iblockId;

		if ($iblockId > 0)
		{
			$query = Iblock\PropertyTable::getList([
				'filter' => [
					'=IBLOCK_ID' => $iblockId,
					'=ACTIVE' => 'Y',
					'=PROPERTY_TYPE' => [
						Iblock\PropertyTable::TYPE_STRING,
						Iblock\PropertyTable::TYPE_NUMBER,
						Iblock\PropertyTable::TYPE_LIST,
					]
				],
				'select' => [ 'ID', 'NAME' ],
				'order' => [ 'ID' => 'ASC' ]
			]);

			while ($row = $query->fetch())
			{
				$result[] = [
					'ID' => 'PROPERTY_' . $row['ID'],
					'VALUE' => '[' . $row['ID'] . '] ' . $row['NAME'],
				];
			}
		}

		return $result;
	}

	public function getMarkingGroupType($code)
	{
		if ($code === '' || !is_string($code)) { return null; }

		if ($this->isKnownCisCodeGroup($code))
		{
			return Market\Data\Trading\MarkingRegistry::CIS;
		}

		return $this->once('getMarkingGroupType', [$code], function($code) {
			$hlblock = $this->markingGroupDataManager();

			if ($hlblock === null) { return null; }

			$query = $hlblock::getList([
				'select' => [ 'UF_NAME' ],
				'filter' => [ '=UF_XML_ID' => $code ],
				'limit' => 1,
			]);
			$row = $query->fetch();

			if (!$row) { return null; }

			$result = null;
			$markerMap = [
				Market\Data\Trading\MarkingRegistry::UIN => static::getLang('TRADING_ENTITY_COMMON_PRODUCT_UIN_GROUP_MARKER'),
			];

			foreach ($markerMap as $type => $markerString)
			{
				$markers = explode(',', $markerString);

				foreach ($markers as $marker)
				{
					if (mb_stripos($row['UF_NAME'], $marker) !== false)
					{
						$result = $type;
						break;
					}
				}

				if ($result !== null) { break; }
			}

			return $result;
		});
	}

	protected function isKnownCisCodeGroup($code)
	{
		return in_array($code, [
			'02',
			'03',
			'05',
			'17485',
			'8258',
			'8721',
			'9840',
			'06',
			'5010',
			'5137',
			'5139',
			'5140',
		], true);
	}

	/** @return Main\Entity\DataManager */
	protected function markingGroupDataManager()
	{
		return $this->once('markingGroupDataManager', null, static function() {
			$field = Catalog\Product\SystemField\MarkingCodeGroup::load();

			if (empty($field['SETTINGS']['HLBLOCK_ID'])) { return null; }
			if (!Main\Loader::includeModule('highloadblock')) { return null; }

			$hlblock = Highloadblock\HighloadBlockTable::getById($field['SETTINGS']['HLBLOCK_ID'])->fetch();

			if ($hlblock === null) { return null; }

			return Highloadblock\HighloadBlockTable::compileEntity($hlblock)->getDataClass();
		});
	}
}