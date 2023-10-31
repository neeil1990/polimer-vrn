<?php

namespace Yandex\Market\Trading\Service\Marketplace\Action\SendBoxes;

use Yandex\Market\Trading\Service as TradingService;

class Request extends TradingService\Reference\Action\DataRequest
{
	public function getOrderId()
	{
		return (int)$this->getRequiredField('orderId');
	}

	public function getOrderNumber()
	{
		return (string)$this->getRequiredField('orderNum');
	}

	public function getShipmentId()
	{
		return $this->getField('shipmentId');
	}

	public function getBoxes()
	{
		return (array)$this->getRequiredField('boxes');
	}
}