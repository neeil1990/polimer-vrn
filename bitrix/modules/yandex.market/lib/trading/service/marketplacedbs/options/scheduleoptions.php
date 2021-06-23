<?php

namespace Yandex\Market\Trading\Service\MarketplaceDbs\Options;

use Bitrix\Main;
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
}
