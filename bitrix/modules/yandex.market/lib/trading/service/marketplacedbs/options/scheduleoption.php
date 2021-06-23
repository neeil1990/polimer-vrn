<?php

namespace Yandex\Market\Trading\Service\MarketplaceDbs\Options;

use Bitrix\Main;
use Yandex\Market;
use Yandex\Market\Trading\Entity as TradingEntity;
use Yandex\Market\Trading\Service as TradingService;
use Bitrix\Main\Localization\Loc;

class ScheduleOption extends IntervalOption
{
	use Market\Reference\Concerns\HasMessage;

	const MATCH_DAY = 'day';
	const MATCH_FULL = 'full';

	/** @var TradingService\MarketplaceDbs\Provider $provider */
	protected $provider;

	public function isMatch(Main\Type\Date $date, $rule = ScheduleOption::MATCH_FULL)
	{
		if (!$this->isMatchDay($date)) { return false; }

		if ($rule === static::MATCH_FULL && $date instanceof Main\Type\DateTime)
		{
			$result = $this->isMatchTime($date);
		}
		else
		{
			$result = true;
		}

		return $result;
	}

	public function isMatchDay(Main\Type\Date $date)
	{
		$dateWeekday = (int)$date->format('N');

		return ($dateWeekday >= $this->getFromWeekday() && $dateWeekday <= $this->getToWeekday());
	}

	public function isValid()
	{
		$fromWeekday = $this->getFromWeekday();
		$toWeekday = $this->getToWeekday();
		$fromTime = $this->getFromTime();
		$toTime = $this->getToTime();

		return (
			$fromWeekday !== null && $toWeekday !== null
			&& $fromWeekday <= $toWeekday
			&& ($fromTime === null || $toTime === null || $fromTime < $toTime)
		);
	}

	/** @return int */
	public function getFromWeekday()
	{
		$value = $this->getValue('FROM_WEEKDAY');

		return Market\Data\WeekDay::sanitize($value);
	}

	/** @return int */
	public function getToWeekday()
	{
		$value = $this->getValue('TO_WEEKDAY');

		return Market\Data\WeekDay::sanitize($value);
	}

	public function getFields(TradingEntity\Reference\Environment $environment, $siteId)
	{
		$selfFields = [
			'FROM_WEEKDAY' => [
				'TYPE' => 'enumeration',
				'MANDATORY' => 'Y',
				'NAME' => static::getMessage('FROM_WEEKDAY'),
				'VALUES' => $this->getWeekdayEnum(),
			],
			'TO_WEEKDAY' => [
				'TYPE' => 'enumeration',
				'MANDATORY' => 'Y',
				'NAME' => static::getMessage('TO_WEEKDAY'),
				'VALUES' => $this->getWeekdayEnum(),
			],
		];

		return $selfFields + parent::getFields($environment, $siteId);
	}

	protected function getWeekdayEnum()
	{
		$result = [];

		for ($day = 1; $day <= 7; ++$day)
		{
			$result[] = [
				'ID' => (string)$day,
				'VALUE' => Loc::getMessage('DOW_' . ($day % 7)),
			];
		}

		return $result;
	}
}
