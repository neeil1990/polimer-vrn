<?php

namespace Yandex\Market\Trading\Service\Marketplace\Action\SendShipmentConfirm;

use Yandex\Market\Trading\Service as TradingService;

class Request extends TradingService\Reference\Action\DataRequest
{
	public function getShipmentId()
	{
		return (int)$this->getRequiredField('shipmentId');
	}

	public function getExternalShipmentId()
	{
		return (string)$this->getRequiredField('externalShipmentId');
	}

	public function getOrderIds()
	{
		return (array)$this->getRequiredField('orderIds');
	}
}