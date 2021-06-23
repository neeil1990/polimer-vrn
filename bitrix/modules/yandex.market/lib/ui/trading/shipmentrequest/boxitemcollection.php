<?php

namespace Yandex\Market\Ui\Trading\ShipmentRequest;

use Bitrix\Main;
use Yandex\Market;

/** @deprecated */
class BoxItemCollection extends Market\Api\Reference\Collection
{
	public static function getItemReference()
	{
		return BoxItem::class;
	}
}