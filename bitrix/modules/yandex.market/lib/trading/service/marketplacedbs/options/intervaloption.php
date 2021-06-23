<?php

namespace Yandex\Market\Trading\Service\MarketplaceDbs\Options;

use Bitrix\Main;
use Yandex\Market;
use Yandex\Market\Trading\Entity as TradingEntity;
use Yandex\Market\Trading\Service as TradingService;

class IntervalOption extends TradingService\Reference\Options\Fieldset
{
	use Market\Reference\Concerns\HasMessage;

	public function isValid()
	{
		$from = $this->getFromTime();
		$to = $this->getToTime();

		return ($from !== null && $to !== null && $from < $to);
	}

	public function isMatchTime(Main\Type\DateTime $date)
	{
		$dateTime = $date->format('H:i');
		$fromTime = $this->getFromTime();
		$toTime = $this->getToTime();
		$result = true;

		if ($fromTime !== null && $dateTime < $fromTime)
		{
			$result = false;
		}
		else if ($toTime !== null && $dateTime > $toTime)
		{
			$result = false;
		}

		return $result;
	}

	public function applyFromTime(Main\Type\DateTime $date)
	{
		$fromTime = $this->getFromTime();
		list($hour, $minutes) = Market\Data\Time::parse($fromTime);

		if ($hour === null)
		{
			$hour = 0;
			$minutes = 0;
		}

		$date = clone $date;
		$date->setTime($hour, $minutes);

		return $date;
	}

	/** @return string|null */
	public function getFromTime()
	{
		$value = $this->getValue('FROM_TIME');

		return Market\Data\Time::sanitize($value);
	}

	/** @return string|null */
	public function getToTime()
	{
		$value = $this->getValue('TO_TIME');

		return Market\Data\Time::sanitize($value);
	}

	public function getFields(TradingEntity\Reference\Environment $environment, $siteId)
	{
		return [
			'FROM_TIME' => [
				'TYPE' => 'time',
				'NAME' => self::getMessage('FROM_TIME'),
			],
			'TO_TIME' => [
				'TYPE' => 'time',
				'NAME' => self::getMessage('TO_TIME'),
			],
		];
	}
}
