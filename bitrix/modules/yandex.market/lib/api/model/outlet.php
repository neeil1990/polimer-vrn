<?php

namespace Yandex\Market\Api\Model;

use Bitrix\Main;
use Yandex\Market;

class Outlet extends Market\Api\Reference\Model
{
	public function getName()
	{
		return (string)$this->getField('name');
	}

	public function getShopOutletCode()
	{
		return (string)$this->getField('shopOutletCode');
	}
}