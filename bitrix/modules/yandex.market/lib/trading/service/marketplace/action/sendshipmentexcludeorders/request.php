<?php

namespace Yandex\Market\Trading\Service\Marketplace\Action\SendShipmentExcludeOrders;

use Yandex\Market\Trading\Service as TradingService;

class Request extends TradingService\Reference\Action\DataRequest
{
	public function getShipmentId()
	{
		return (int)$this->getRequiredField('shipmentId');
	}

	public function getOrderIds()
	{
		return (array)$this->getRequiredField('orderIds');
	}
}