<?php

namespace Yandex\Market\Trading\Service\Marketplace\Model;

class ShipmentDetails extends Shipment
{
	public function getOrderIds()
	{
		return (array)$this->getRequiredField('orderIds');
	}
}