<?php

namespace Yandex\Market\Api\Model\Order;

use Bitrix\Main;
use Yandex\Market;

class Item extends Market\Api\Model\Cart\Item
{
	public function getPrice()
	{
		return (float)$this->getRequiredField('price');
	}

	public function getSubsidy()
	{
		return (float)$this->getField('subsidy');
	}

	public function getFullPrice()
	{
		return $this->getPrice() + $this->getSubsidy();
	}

	public function getVat()
	{
		return (string)$this->getField('vat');
	}
}