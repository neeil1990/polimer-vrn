<?php

namespace Yandex\Market\Api\Delivery\Services\Model;

use Yandex\Market;

/** @method DeliveryService current() */
class DeliveryServiceCollection extends Market\Api\Reference\Collection
{
	public static function getItemReference()
	{
		return DeliveryService::class;
	}
}