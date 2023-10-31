<?php

namespace Yandex\Market\Ui\Trading\ShipmentRequest;

use Yandex\Market;

class BasketConfirm extends Market\Api\Reference\Model
{
	public function getReason()
	{
		return (string)$this->getRequiredField('REASON');
	}
}