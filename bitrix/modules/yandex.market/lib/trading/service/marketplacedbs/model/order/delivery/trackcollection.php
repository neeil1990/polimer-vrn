<?php

namespace Yandex\Market\Trading\Service\MarketplaceDbs\Model\Order\Delivery;

use Yandex\Market;

/** @deprecated */
class TrackCollection extends Market\Api\Model\Order\TrackCollection
{
	public static function getItemReference()
	{
		return Track::class;
	}
}