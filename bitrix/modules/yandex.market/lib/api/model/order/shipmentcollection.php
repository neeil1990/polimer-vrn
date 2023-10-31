<?php

namespace Yandex\Market\Api\Model\Order;

use Bitrix\Main;
use Yandex\Market;

class ShipmentCollection extends Market\Api\Reference\Collection
{
	public static function getItemReference()
	{
		return Shipment::class;
	}
}