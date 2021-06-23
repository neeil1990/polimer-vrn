<?php

namespace Yandex\Market\Trading\Service\Marketplace\Model;

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