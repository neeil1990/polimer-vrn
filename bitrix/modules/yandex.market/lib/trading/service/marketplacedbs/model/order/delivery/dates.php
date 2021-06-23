<?php

namespace Yandex\Market\Trading\Service\MarketplaceDbs\Model\Order\Delivery;

use Yandex\Market;
use Bitrix\Main;
use Yandex\Market\Trading\Service as TradingService;

class Dates extends Market\Api\Reference\Model
{
	/** @return Main\Type\Date|null */
	public function getFromDate()
	{
		$value = (string)$this->getField('fromDate');

		return $value !== '' ? Market\Data\Date::convertFromService($value) : null;
	}

	/** @return Main\Type\Date|null */
	public function getToDate()
	{
		$value = (string)$this->getField('toDate');

		return $value !== '' ? Market\Data\Date::convertFromService($value) : null;
	}

	public function getFromTime()
	{
		return $this->getField('fromTime');
	}

	public function getToTime()
	{
		return $this->getField('toTime');
	}
}