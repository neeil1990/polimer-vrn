<?php

namespace Yandex\Market\Trading\Service\Marketplace\Api\Order;

use Bitrix\Main;
use Yandex\Market;
use Yandex\Market\Trading\Service as TradingService;

class Request extends Market\Api\Partner\Order\Request
{
	public function buildResponse($data)
	{
		return new Response($data);
	}
}