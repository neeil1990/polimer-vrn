<?php

namespace Yandex\Market\Trading\Service\MarketplaceDbs\Model;

use Yandex\Market;
use Bitrix\Main;
use Yandex\Market\Trading\Service as TradingService;

class OrderCollection extends Market\Api\Model\OrderCollection
{
	public static function getItemReference()
	{
		return Order::class;
	}
}