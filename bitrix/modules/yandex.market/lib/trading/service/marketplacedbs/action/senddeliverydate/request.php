<?php

namespace Yandex\Market\Trading\Service\MarketplaceDbs\Action\SendDeliveryDate;

use Bitrix\Main\ArgumentException;
use Yandex\Market;
use Yandex\Market\Trading\Service as TradingService;

class Request extends TradingService\Common\Action\SendRequest
{
	public function getDate()
	{
		$value = $this->getRequiredField('date');
		$result = Market\Data\Date::sanitize($value);

		if ($result === null)
		{
			throw new ArgumentException(sprintf('cant parse %s as date', $value));
		}

		return $result;
	}

	public function getReason()
	{
		return (string)$this->getRequiredField('reason');
	}
}