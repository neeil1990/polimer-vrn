<?php

namespace Yandex\Market\Trading\Service\MarketplaceDbs\Api\SendStatus;

use Yandex\Market;
use Yandex\Market\Trading\Service as TradingService;

class Response extends Market\Api\Partner\SendStatus\Response
{
	protected function loadOrder()
	{
		$data = (array)$this->getRequiredField('order');

		return new TradingService\MarketplaceDbs\Model\Order($data);
	}
}