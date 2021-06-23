<?php

namespace Yandex\Market\Ui\Trading\ShipmentRequest;

use Bitrix\Main;
use Yandex\Market;

class ShipmentCollection extends Market\Api\Reference\Collection
{
	public static function getItemReference()
	{
		return Shipment::class;
	}
}