<?php

namespace Yandex\Market\Trading\Service\Marketplace\Api\Order;

use Bitrix\Main;
use Yandex\Market;
use Yandex\Market\Trading\Service as TradingService;

class Response  extends Market\Api\Partner\Order\Response
{
	protected function loadOrder()
	{
		$data = (array)$this->getField('order');

		return new TradingService\Marketplace\Model\Order($data);
	}
}