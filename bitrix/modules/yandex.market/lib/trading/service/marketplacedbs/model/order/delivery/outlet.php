<?php

namespace Yandex\Market\Trading\Service\MarketplaceDbs\Model\Order\Delivery;

use Yandex\Market;
use Bitrix\Main;
use Yandex\Market\Trading\Service as TradingService;

class Outlet extends Market\Api\Reference\Model
{
	/** @return string|null */
	public function getCode()
	{
		return $this->getRequiredField('code');
	}
}