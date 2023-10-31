<?php

namespace Yandex\Market\Trading\Service\MarketplaceDbs\Action\SendDeliveryStorageLimit;

use Bitrix\Main\ArgumentException;
use Yandex\Market;
use Yandex\Market\Trading\Service as TradingService;

class Request extends TradingService\Common\Action\SendRequest
{
	public function getNewDate()
	{
		$value = $this->getRequiredField('newDate');
		$result = Market\Data\Date::sanitize($value);

		if ($result === null)
		{
			throw new ArgumentException(sprintf('cant parse %s as date', $value));
		}

		return $result;
	}
}