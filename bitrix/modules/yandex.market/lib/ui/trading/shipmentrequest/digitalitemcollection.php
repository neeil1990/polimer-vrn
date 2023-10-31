<?php

namespace Yandex\Market\Ui\Trading\ShipmentRequest;

use Yandex\Market;

class DigitalItemCollection extends Market\Api\Reference\Collection
{
	public static function getItemReference()
	{
		return DigitalItem::class;
	}
}