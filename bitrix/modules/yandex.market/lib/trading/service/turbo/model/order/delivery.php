<?php

namespace Yandex\Market\Trading\Service\Turbo\Model\Order;

use Yandex\Market;
use Bitrix\Main;

class Delivery extends Market\Api\Reference\Model
{
	public function getPrice()
	{
		return (float)$this->getField('price');
	}
}