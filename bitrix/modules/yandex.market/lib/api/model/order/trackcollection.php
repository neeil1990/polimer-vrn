<?php

namespace Yandex\Market\Api\Model\Order;

use Yandex\Market;

/** @method Track current() */
class TrackCollection extends Market\Api\Reference\Collection
{
	public static function getItemReference()
	{
		return Track::class;
	}
}