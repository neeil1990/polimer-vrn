<?php

namespace Yandex\Market\Trading\Service\Marketplace\Model;

use Yandex\Market;
use Bitrix\Main;
use Yandex\Market\Trading\Service as TradingService;

class OrderFacade extends Market\Api\Model\OrderFacade
{
	protected static function createLoadListRequest()
	{
		return new TradingService\Marketplace\Api\Orders\Request();
	}

	protected static function createLoadRequest()
	{
		return new TradingService\Marketplace\Api\Order\Request();
	}
}