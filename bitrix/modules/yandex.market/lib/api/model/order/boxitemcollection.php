<?php

namespace Yandex\Market\Api\Model\Order;

use Bitrix\Main;
use Yandex\Market;

class BoxItemCollection extends Market\Api\Reference\Collection
{
	public static function getItemReference()
	{
		return BoxItem::class;
	}
}