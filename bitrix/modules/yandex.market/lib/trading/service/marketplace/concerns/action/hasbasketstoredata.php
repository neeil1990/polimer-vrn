<?php

namespace Yandex\Market\Trading\Service\Marketplace\Concerns\Action;

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
 * @method array mergeBasketData($dataList)
 */
trait HasBasketStoreData
{
	protected function getStoreData($productIds, $quantities, $context)
	{
		$options = $this->provider->getOptions();
		$storeEntity = $this->environment->getStore();
		$context += [
			'RESERVE' => $options->useOrderReserve(),
			'TRACE' => $options->isProductStoresTrace(),
			'STORES' => $options->getProductStores(),
		];

		$result = $storeEntity->getBasketData($productIds, $quantities, $context);
		$result = $this->applyStoreDataReserves($result);

		return $result;
	}

	protected function applyStoreDataReserves($storeData)
	{
		if (!$this->provider->getOptions()->useOrderReserve()) { return $storeData; }

		$command = new TradingService\Marketplace\Command\BasketReserves(
			$this->provider,
			$this->environment,
			$this->getPlatform()
		);

		return $command->execute($storeData);
	}
}