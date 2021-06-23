<?php

namespace Yandex\Market\Trading\Service\MarketplaceDbs;

use Yandex\Market;
use Bitrix\Main;
use Yandex\Market\Trading\Entity as TradingEntity;
use Yandex\Market\Trading\Service as TradingService;

class ModelFactory extends TradingService\Marketplace\ModelFactory
{
	public function getCartClassName()
	{
		return Model\Cart::class;
	}

	public function getOrderClassName()
	{
		return Model\Order::class;
	}
}