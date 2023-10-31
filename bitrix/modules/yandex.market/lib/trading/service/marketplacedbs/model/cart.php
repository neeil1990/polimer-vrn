<?php

namespace Yandex\Market\Trading\Service\MarketplaceDbs\Model;

use Yandex\Market;
use Bitrix\Main;
use Yandex\Market\Trading\Service as TradingService;

/** @method Cart\Delivery getDelivery() */
class Cart extends TradingService\Marketplace\Model\Cart
{
	protected function getChildModelReference()
	{
		return [
			'delivery' => Cart\Delivery::class,
		];
	}
}