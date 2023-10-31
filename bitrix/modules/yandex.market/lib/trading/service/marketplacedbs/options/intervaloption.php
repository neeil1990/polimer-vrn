<?php

namespace Yandex\Market\Trading\Service\MarketplaceDbs\Options;

use Bitrix\Main;
use Yandex\Market;
use Yandex\Market\Trading\Entity as TradingEntity;
use Yandex\Market\Trading\Service as TradingService;

class IntervalOption extends TradingService\Reference\Options\Fieldset
{
	use Market\Reference\Concerns\HasMessage;

	const MATCH_UNTIL_END = 'untilEnd';
	const MATCH_AFTER_START = 'afterStart';
	const MATCH_FULL = 'full';

	public function isValid()
	{
		$from = $this->getFromTime();
		$to = $this->getToTime();

		return ($from !== null && $to !== null && $from < $to);
	}

	public function isMatch(Main\Type\Date $date, $rule = IntervalOption::MATCH_FULL)
	{
		if (!($date instanceof Main\Type\DateTime)) { return true; }

		return $this->isMatchTime($date, $rule);
	}

	public function isMatchTime(Main\Type\DateTime $date, $rule = IntervalOption::MATCH_FULL)
	{
		$dateTime = $date->format('H:i');

		return $this->isMatchTimeValue($dateTime, $rule);
	}

	public function isMatchTimeValue($time, $rule = IntervalOption::MATCH_FULL)
	{
		if ($time === null) { return true; }

		$fromTime = $this->getFromTime();
		$toTime = $this->getToTime();
		$result = true;

		if ($fromTime !== null && $time < $fromTime && $rule !== static::MATCH_UNTIL_END)
		{
			$result = false;
		}
		else if ($toTime !== null && $time > $toTime && $rule !== static::MATCH_AFTER_START)
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
