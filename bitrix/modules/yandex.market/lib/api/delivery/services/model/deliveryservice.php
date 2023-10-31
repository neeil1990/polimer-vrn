<?php

namespace Yandex\Market\Api\Delivery\Services\Model;

use Yandex\Market;

class DeliveryService extends Market\Api\Reference\Model
{
	public function getId()
	{
		return (int)$this->getRequiredField('id');
	}

	public function getName()
	{
		return (string)$this->getField('name');
	}
}