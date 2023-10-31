<?php

namespace Yandex\Market\Api\Business\Warehouses\Model;

use Yandex\Market;

/** @method WarehouseGroup current() */
class WarehouseGroupCollection extends Market\Api\Reference\Collection
{
	public static function getItemReference()
	{
		return WarehouseGroup::class;
	}
}