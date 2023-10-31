<?php

namespace Yandex\Market\Api\Business\Warehouses\Model;

use Yandex\Market;

/** @method Warehouse current() */
class WarehouseCollection extends Market\Api\Reference\Collection
{
	public static function getItemReference()
	{
		return Warehouse::class;
	}
}