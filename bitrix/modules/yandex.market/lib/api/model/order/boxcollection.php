<?php

namespace Yandex\Market\Api\Model\Order;

use Bitrix\Main;
use Yandex\Market;

class BoxCollection extends Market\Api\Reference\Collection
{
	public static function getItemReference()
	{
		return Box::class;
	}
}