<?php

namespace Yandex\Market\Trading\Service\MarketplaceDbs\Model;

use Yandex\Market;
use Yandex\Market\Trading\Service as TradingService;

class OrderFacade extends Market\Api\Model\OrderFacade
{
	protected static function createLoadListRequest()
	{
		return new TradingService\MarketplaceDbs\Api\Orders\Request();
	}

	protected static function createLoadRequest()
	{
		return new TradingService\MarketplaceDbs\Api\Order\Request();
	}

	protected static function createSubmitStatusRequest()
	{
		return  new TradingService\MarketplaceDbs\Api\SendStatus\Request();
	}
}