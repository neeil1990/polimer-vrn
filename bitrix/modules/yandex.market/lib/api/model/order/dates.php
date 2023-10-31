<?php

namespace Yandex\Market\Api\Model\Order;

use Yandex\Market;
use Bitrix\Main;

class Dates extends Market\Api\Reference\Model
{
	public function getFrom()
	{
		$date = $this->getFromDate();
		$time = $this->getFromTime();

		return $this->combineDateTime($date, $time);
	}

	public function getTo()
	{
		$date = $this->getToDate();
		$time = $this->getToTime();

		return $this->combineDateTime($date, $time);
	}

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

	public function getRealDeliveryDate()
	{
		$value = (string)$this->getField('realDeliveryDate');

		return $value !== '' ? Market\Data\Date::convertFromService($value) : null;
	}

	protected function combineDateTime(Main\Type\Date $date = null, $time = null)
	{
		if ($date === null) { return null; }
		if ($time === null) { return $date; }

		$timeParts = Market\Data\Time::parse($time);

		if ($timeParts === null) { return $date; }

		$dateWithTime = Main\Type\DateTime::createFromTimestamp($date->getTimestamp());
		$dateWithTime->setTime($timeParts[0], $timeParts[1]);

		return $dateWithTime;
	}
}