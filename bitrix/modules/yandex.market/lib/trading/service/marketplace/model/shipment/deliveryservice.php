<?php

namespace Yandex\Market\Trading\Service\Marketplace\Model\Shipment;

use Yandex\Market;

class DeliveryService extends Market\Api\Reference\Model
{
	public function getId()
	{
		return (int)$this->getField('id');
	}

	public function getName()
	{
		return (string)$this->getField('name');
	}
}