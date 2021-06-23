<?php

namespace Yandex\Market\Trading\Service\MarketplaceDbs\Options;

use Bitrix\Main;
use Yandex\Market\Trading\Service as TradingService;

class Timetable
{
	protected $schedule;
	protected $holiday;

	public function __construct(
		ScheduleOptions $schedule,
		HolidayOption $holiday
	)
	{
		$this->schedule = $schedule;
		$this->holiday = $holiday;
	}

	public function isMatch(Main\Type\Date $date)
	{
		$intervals = $this->getIntervals($date);

		return !empty($intervals);
	}

	/**
	 * @param Main\Type\Date $date
	 *
	 * @return IntervalOption[]
	 * @throws Main\SystemException
	 */
	public function getIntervals(Main\Type\Date $date)
	{
		$intervals = [];

		$isHoliday = $this->holiday->isHoliday($date);
		$isHolidayWorkday = $this->holiday->isWorkday($date);

		if ($isHoliday && !$isHolidayWorkday)
		{
			// nothing
		}
		else if ($isHolidayWorkday && $this->holiday->getIntervals()->hasValid())
		{
			foreach ($this->holiday->getIntervals() as $interval)
			{
				$intervals[] = $interval;
			}
		}
		else if ($this->schedule->hasValid())
		{
			$intervals = $this->schedule->getMatchOptions(
				$date,
				TradingService\MarketplaceDbs\Options\ScheduleOption::MATCH_DAY
			);

			if (empty($intervals) && $isHolidayWorkday)
			{
				$nearestDate = $this->getNearestWorkingDay($date);

				$intervals = $this->schedule->getMatchOptions(
					$nearestDate,
					TradingService\MarketplaceDbs\Options\ScheduleOption::MATCH_DAY
				);
			}
		}
		else
		{
			$intervals[] = $this->schedule->makeFullDayOption(); // day without time
		}

		return $intervals;
	}

	protected function getNearestWorkingDay(Main\Type\Date $date)
	{
		$iteratorCount = 0;
		$iteratorLimit = 7;
		$leftDate = clone $date;
		$rightDate = clone $date;
		$result = null;

		do
		{
			if ($iteratorCount > $iteratorLimit) { throw new Main\SystemException('cant find nearest working day'); }

			$leftDate->add('-P1D');
			$rightDate->add('P1D');

			foreach ([$leftDate, $rightDate] as $compareDate)
			{
				$options = $this->schedule->getMatchOptions($compareDate, ScheduleOption::MATCH_DAY);

				if (empty($options)) { continue; }

				$result = $compareDate;

				if ($result instanceof Main\Type\DateTime)
				{
					$result = $this->applyIntervals($result, $options);
				}
			}

			++$iteratorCount;
		}
		while ($result === null);

		return $result;
	}

	/**
	 * @param Main\Type\DateTime $date
	 * @param IntervalOption[] $intervals
	 *
	 * @return Main\Type\DateTime
	 * @throws Main\ArgumentException
	 */
	public function applyIntervals(Main\Type\DateTime $date, array $intervals)
	{
		foreach ($intervals as $interval)
		{
			if (!$interval->isMatchTime($date)) { continue; }

			return $date;
		}

		$firstInterval = reset($intervals);

		if ($firstInterval === false)
		{
			throw new Main\ArgumentException('cant apply schedule time without specified periods');
		}

		return $firstInterval->applyFromTime($date);
	}
}