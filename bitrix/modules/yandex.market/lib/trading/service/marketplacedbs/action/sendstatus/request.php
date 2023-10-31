<?php

namespace Yandex\Market\Trading\Service\MarketplaceDbs\Action\SendStatus;

use Yandex\Market;
use Yandex\Market\Trading\Service as TradingService;

class Request extends TradingService\Marketplace\Action\SendStatus\Request
{
	public function getCancelReason()
	{
		return $this->getField('cancelReason');
	}

	public function hasRealDeliveryDate()
	{
		return $this->hasField('realDeliveryDate');
	}

	public function getRealDeliveryDate()
	{
		$value = $this->getField('realDeliveryDate');

		return $value !== null ? Market\Data\Date::sanitize($value) : null;
	}
}