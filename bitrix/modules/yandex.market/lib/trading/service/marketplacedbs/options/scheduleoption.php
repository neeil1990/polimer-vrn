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

	const WEEKDAY_FIRST = 1;
	const WEEKDAY_LAST = 7;

	/** @var TradingService\MarketplaceDbs\Provider $provider */
	protected $provider;

	public function hasIntersection(ScheduleOption $target)
	{
		$fromTime = $this->getFromTime();
		$toTime = $this->getToTime();
		$result = false;

		for ($weekday = $this->getFromWeekday(); $this->isMatchWeekdayValue($weekday); $weekday = ($weekday % static::WEEKDAY_LAST) + 1)
		{
			if (!$target->isMatchWeekdayValue($weekday)) { continue; }
			if (!$target->isMatchTimeValue($fromTime, static::MATCH_UNTIL_END)) { continue; }
			if (!$target->isMatchTimeValue($toTime, static::MATCH_AFTER_START)) { continue; }

			$result = true;
			break;
		}

		return $result;
	}

	public function isMatch(Main\Type\Date $date, $rule = ScheduleOption::MATCH_FULL)
	{
		if (!$this->isMatchDay($date)) { return false; }

		if ($rule !== static::MATCH_DAY)
		{
			$result = parent::isMatch($date, $rule);
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

		return $this->isMatchWeekdayValue($dateWeekday);
	}

	public function isMatchWeekdayValue($weekday)
	{
		$from = $this->getFromWeekday();
		$to = $this->getToWeekday();

		if ($from <= $to)
		{
			$result = ($weekday >= $from && $weekday <= $to);
		}
		else
		{
			$result = ($weekday >= $from || $weekday <= $to);
		}

		return $result;
	}

	public function isValid()
	{
		$fromWeekday = $this->getFromWeekday();
		$toWeekday = $this->getToWeekday();
		$fromTime = $this->getFromTime();
		$toTime = $this->getToTime();

		return (
			$this->isValidWeekday($fromWeekday)
			&& $this->isValidWeekday($toWeekday)
			&& ($fromTime === null || $toTime === null || $fromTime < $toTime)
		);
	}

	protected function isValidWeekday($number)
	{
		return ($number !== null && $number >= static::WEEKDAY_FIRST && $number <= static::WEEKDAY_LAST);
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

	public function getFieldDescription(TradingEntity\Reference\Environment $environment, $siteId)
	{
		return parent::getFieldDescription($environment, $siteId) + [
			'SETTINGS' => [
				'SUMMARY' => '#FROM_WEEKDAY#-#TO_WEEKDAY# (#FROM_TIME#-#TO_TIME#)',
				'VALIGN_PUSH' => true,
			],
		];
	}

	public function getFields(TradingEntity\Reference\Environment $environment, $siteId)
	{
		$selfFields = [
			'FROM_WEEKDAY' => [
				'TYPE' => 'enumeration',
				'MANDATORY' => 'Y',
				'NAME' => static::getMessage('FROM_WEEKDAY'),
				'VALUES' => $this->getWeekdayEnum(),
				'SETTINGS' => [
					'DEFAULT_VALUE' => static::WEEKDAY_FIRST,
				],
			],
			'TO_WEEKDAY' => [
				'TYPE' => 'enumeration',
				'MANDATORY' => 'Y',
				'NAME' => static::getMessage('TO_WEEKDAY'),
				'VALUES' => $this->getWeekdayEnum(),
				'SETTINGS' => [
					'DEFAULT_VALUE' => static::WEEKDAY_LAST - 2,
				],
			],
		];

		return $selfFields + parent::getFields($environment, $siteId);
	}

	protected function getWeekdayEnum()
	{
		$result = [];

		for ($day = static::WEEKDAY_FIRST; $day <= static::WEEKDAY_LAST; ++$day)
		{
			$langKey = 'DOW_' . ($day % 7);

			$result[] = [
				'ID' => (string)$day,
				'VALUE' => self::getMessage($langKey, null, Loc::getMessage($langKey)),
			];
		}

		return $result;
	}
}
