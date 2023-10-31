<?php

namespace Yandex\Market\Trading\Service\MarketplaceDbs;

use Yandex\Market\Trading\Service as TradingService;

class ModelFactory extends TradingService\Marketplace\ModelFactory
{
	public function getCartClassName()
	{
		return Model\Cart::class;
	}

	public function getOrderFacadeClassName()
	{
		return Model\OrderFacade::class;
	}

	public function getOrderClassName()
	{
		return Model\Order::class;
	}

	public function getBuyerClassName()
	{
		return Model\Order\Buyer::class;
	}
}