<?php

namespace Yandex\Market\Ui\Trading\ShipmentRequest;

use Yandex\Market;

class DigitalItem extends Market\Api\Reference\Model
{
	public function getCode()
	{
		return (string)$this->getRequiredField('CODE');
	}

	public function getActivateTill()
	{
		return $this->getRequiredField('ACTIVATE_TILL');
	}
}