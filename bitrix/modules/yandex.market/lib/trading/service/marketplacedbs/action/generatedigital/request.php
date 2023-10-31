<?php

namespace Yandex\Market\Trading\Service\MarketplaceDbs\Action\GenerateDigital;

use Yandex\Market\Trading\Service as TradingService;

class Request extends TradingService\Common\Action\TaskRequest
{
	public function getShopDeliveryId()
	{
		return $this->getRequiredField('shopDeliveryId');
	}
}