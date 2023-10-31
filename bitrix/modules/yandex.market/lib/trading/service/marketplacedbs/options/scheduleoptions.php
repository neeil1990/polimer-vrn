<?php

namespace Yandex\Market\Trading\Service\MarketplaceDbs\Options;

use Bitrix\Main;
use Yandex\Market\Data;
use Yandex\Market\Trading\Service as TradingService;

/** @method ScheduleOption current() */
/** @property  ScheduleOption[] collection */
class ScheduleOptions extends IntervalOptions
{
	public function getItemReference()
	{
		return ScheduleOption::class;
	}

	public function makeFullDayOption()
	{
		return new ScheduleOption($this->provider);
	}

	public function hasIntersection(ScheduleOptions $target)
	{
		if ($this->count() === 0) { return true; }
		if ($target->count() === 0) { return true; }

		$result = false;

		foreach ($this->collection as $scheduleOption)
		{
			foreach ($target as $targetOption)
			{
				if (!$scheduleOption->hasIntersection($targetOption)) { continue; }

				$result = true;
				break;
			}

			if ($result) { break; }
		}

		return $result;
	}

	public function isMatch(Main\Type\Date $date, $rule = ScheduleOption::MATCH_FULL)
	{
		$periods = $this->getMatchOptions($date, $rule);

		return !empty($periods);
	}

	/**
	 * @param Main\Type\Date $date
	 * @param string $rule
	 *
	 * @return ScheduleOption[]
	 */
	public function getMatchOptions(Main\Type\Date $date, $rule = ScheduleOption::MATCH_FULL)
	{
		$result = [];

		foreach ($this->collection as $option)
		{
			if ($option->isMatch($date, $rule))
			{
				$result[] = $option;
			}
		}

		return $result;
	}

	public function firstUntilTime()
	{
		/** @var TradingService\MarketplaceDbs\Options\ScheduleOption|null $firstOption */
		$firstOption = $this->offsetGet(0);

		if ($firstOption === null) { return [24, 0]; }

		$result = Data\Time::parse($firstOption->getToTime());

		foreach ($this->collection as $option)
		{
			if ($option->getFromWeekday() !== $firstOption->getFromWeekday()) { break; }

			$optionTime = Data\Time::parse($option->getToTime());

			if ($optionTime === null) { continue; }

			if ($result === null || Data\Time::compare($result, $optionTime) === -1)
			{
				$result = $optionTime;
			}
		}

		if ($result === null || ($result[0] === 0 && $result[1] === 0))
		{
			$result = [24, 0];
		}

		return $result;
	}
}
