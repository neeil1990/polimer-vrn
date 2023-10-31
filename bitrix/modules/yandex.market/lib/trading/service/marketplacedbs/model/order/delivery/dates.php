<?php

namespace Yandex\Market\Trading\Service\MarketplaceDbs\Model\Order\Delivery;

use Yandex\Market;

class Dates extends Market\Api\Model\Order\Dates
{
	/** @deprecated */
	public function getOutletStorageLimitDate()
	{
		$value = (string)$this->getField('outletStorageLimitDate');

		return $value !== '' ? Market\Data\Date::convertFromService($value) : null;
	}
}