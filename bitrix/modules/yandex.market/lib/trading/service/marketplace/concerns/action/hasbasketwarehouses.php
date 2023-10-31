<?php

namespace Yandex\Market\Trading\Service\Marketplace\Concerns\Action;

use Yandex\Market;
use Yandex\Market\Trading\Entity as TradingEntity;
use Yandex\Market\Trading\Service as TradingService;

/**
 * trait HasBasketWarehouses
 * @property TradingService\Marketplace\Provider $provider
 * @property TradingEntity\Reference\Environment $environment
 * @property TradingService\Marketplace\Action\Cart\Request|TradingService\Marketplace\Action\OrderAccept\Request $request
 * @method array makeBasketContext()
 * @method array applyQuantitiesRatio($quantities, $packRatio)
 * @method array getProductData($productIds, $quantities, $context)
 * @method array getPriceData($productIds, $quantities, $context)
 * @method array getStoreData($productIds, $quantities, $context)
 * @method array mergeBasketData($dataList)
 */
trait HasBasketWarehouses
{
	protected function getBasketData(Market\Api\Model\Cart\ItemCollection $items, $offerMap = null, $packRatio = null)
	{
		$context = $this->makeBasketContext();
		$productIds = $offerMap !== null ? array_values($offerMap) : $items->getOfferIds();
		$quantities = $items->getQuantities($offerMap);
		$quantities = $this->applyQuantitiesRatio($quantities, $packRatio);

		if (empty($productIds)) { return []; }

		$dataGroups = [
			$this->getProductData($productIds, $quantities, $context),
			$this->getPriceData($productIds, $quantities, $context),
		];

		if ($this->provider->getOptions()->useWarehouses())
		{
			$warehouseQuantities = $this->makeBasketWarehouseQuantities($items, $offerMap);
			$warehouseQuantities = $this->applyWarehouseQuantitiesRatio($warehouseQuantities, $packRatio);

			$dataGroups[] = $this->getStoreDataByWarehouses($warehouseQuantities, $context);
		}
		else
		{
			$dataGroups[] = $this->getStoreData($productIds, $quantities, $context);
		}

		return $this->mergeBasketData($dataGroups);
	}

	protected function makeBasketWarehouseQuantities(Market\Api\Model\Cart\ItemCollection $items, $offerMap = null)
	{
		$result = [];

		/** @var TradingService\Marketplace\Model\Cart\Item|TradingService\Marketplace\Model\Order\Item $item */
		foreach ($items as $item)
		{
			$warehouseId = $item->getPartnerWarehouseId();
			$productId = $item->mapProductId($offerMap);

			if ($productId === null) { continue; }

			if (!isset($result[$warehouseId]))
			{
				$result[$warehouseId] = [];
			}

			if (!isset($result[$warehouseId][$productId]))
			{
				$result[$warehouseId][$productId] = [];
			}

			$result[$warehouseId][$productId][] = $item->getCount();
		}

		return $result;
	}

	protected function applyWarehouseQuantitiesRatio($warehouseQuantities, $packRatio)
	{
		foreach ($warehouseQuantities as &$oneQuantities)
		{
			$oneQuantities = $this->applyQuantitiesRatio($oneQuantities, $packRatio);
		}
		unset($oneQuantities);

		return $warehouseQuantities;
	}

	protected function getStoreDataByWarehouses($warehouseQuantities, $context)
	{
		$options = $this->provider->getOptions();
		$storeEntity = $this->environment->getStore();
		$warehouseField = $options->getWarehouseStoreField();
		$result = [];

		foreach ($warehouseQuantities as $warehouseId => $quantities)
		{
			$productIds = array_keys($quantities);
			$stores = $storeEntity->findStores($warehouseField, $warehouseId);
			$storeContext = $context + [
				'TRACE' => true,
				'STORES' => $stores,
			];

			$result[] = $storeEntity->getBasketData($productIds, $quantities, $storeContext);
		}

		return $this->mergeBasketData($result);
	}
}