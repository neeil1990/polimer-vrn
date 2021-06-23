<?php

namespace Yandex\Market\Export\Promo\Discount;

use Yandex\Market;
use Bitrix\Main;

class WeekdayCondition
{
	const WEEK_LENGTH = 7;

	/** @var int[] */
	protected $weekdays;
	/** @var Main\Type\Date */
	protected $today;

	public function __construct(array $weekdays, Main\Type\Date $today = null)
	{
		$this->weekdays = $weekdays;
		$this->today = $today !== null ? $today : new Main\Type\DateTime();
	}

	public function getDateRange($startDate, $finishDate)
	{
		$startDate = $this->sanitizeDate($startDate);
		$finishDate = $this->sanitizeDate($finishDate);

		list($calculateFrom, $calculateDirection) = $this->rangeCalculationStart($startDate, $finishDate);

		if ($calculateFrom !== null)
		{
			list($matchedStart, $matchedFinish) = $this->rangeMatchedDates($calculateFrom, $calculateDirection, $startDate, $finishDate);
		}
		else
		{
			$matchedStart = null;
			$matchedFinish = null;
		}

		return [$matchedStart, $matchedFinish];
	}

	protected function sanitizeDate($date)
	{
		$result = null;

		if ($date instanceof Main\Type\Date)
		{
			$result = $date;
		}
		else if ($date instanceof \DateTime)
		{
			$result = Main\Type\DateTime::createFromPhp($date);
		}

		return $result;
	}

	protected function isDateMatched(Main\Type\Date $date)
	{
		return in_array((int)$date->format('N'), $this->weekdays, true);
	}

	protected function rangeCalculationStart(Main\Type\Date $startDate = null, Main\Type\Date $finishDate = null)
	{
		$todayStart = $this->roundDate($this->today, false);
		$todayFinish = $this->roundDate($this->today, true);
		$calculateFrom = null;
		$calculateDirection = true;

		if ($finishDate !== null && $finishDate->getTimestamp() <= $todayFinish->getTimestamp()) // past
		{
			$calculateFrom = $finishDate;
			$calculateDirection = false;
		}
		else if ($startDate !== null && $startDate->getTimestamp() >= $todayStart->getTimestamp()) // future
		{
			$calculateFrom = $startDate;
			$calculateDirection = true;
		}
		else if ($this->isDateMatched($todayStart)) // now
		{
			$calculateFrom = $this->getLastMatchedDate($todayStart, false, $startDate);
			$calculateDirection = true;
		}
		else // near now
		{
			$rangeStartAfterToday = $this->getNextMatchedDate($todayStart, true, $finishDate);

			if ($rangeStartAfterToday !== null)
			{
				$calculateFrom = $rangeStartAfterToday;
				$calculateDirection = true;
			}
			else
			{
				$rangeEndBeforeToday = $this->getNextMatchedDate($todayFinish, false, $startDate);

				if ($rangeEndBeforeToday !== null)
				{
					$calculateFrom = $rangeEndBeforeToday;
					$calculateDirection = false;
				}
			}
		}

		return [$calculateFrom, $calculateDirection];
	}

	protected function rangeMatchedDates(Main\Type\Date $calculateFrom, $calculateDirection, Main\Type\Date $startDate = null, Main\Type\Date $finishDate = null)
	{
		$matchedStart = null;
		$matchedFinish = null;

		if ($calculateDirection)
		{
			$matchedStart = $this->getNextMatchedDate($calculateFrom, true, $finishDate);

			if ($matchedStart !== null)
			{
				$matchedFinish = $this->getLastMatchedDate($matchedStart, true, $finishDate);
			}
		}
		else
		{
			$matchedFinish = $this->getNextMatchedDate($calculateFrom, false, $startDate);

			if ($matchedFinish !== null)
			{
				$matchedStart = $this->getLastMatchedDate($matchedFinish, false, $startDate);
			}
		}

		return [$matchedStart, $matchedFinish];
	}

	protected function getNextMatchedDate(Main\Type\Date $date, $direction = true, Main\Type\Date $limit = null)
	{
		$result = null;

		if ($this->isDateMatched($date))
		{
			$result = clone $date;
		}
		else
		{
			$interval = ($direction ? 'P1D' : '-P1D');
			$iterationDate = $this->roundDate($date, !$direction);
			$repeatIndex = 0;

			do
			{
				$iterationDate = $iterationDate->add($interval);
				$iterationDate = $this->limitDate($iterationDate, $limit, $direction);

				if ($this->isDateMatched($iterationDate))
				{
					$result = clone $iterationDate;
					break;
				}

				++$repeatIndex;
			}
			while (
				$repeatIndex < static::WEEK_LENGTH
				&& !$this->isLimitDateReached($iterationDate, $limit, $direction)
			);
		}

		return $result;
	}

	protected function getLastMatchedDate(Main\Type\Date $date, $direction = true, Main\Type\Date $limit = null)
	{
		$interval = ($direction ? 'P1D' : '-P1D');
		$repeatIndex = 0;
		$iterationDate = $this->roundDate($date, $direction);
		$iterationDate = $this->limitDate($iterationDate, $limit, $direction);
		$result = null;

		while ($repeatIndex < static::WEEK_LENGTH)
		{
			if (!$this->isDateMatched($iterationDate)) { break; }

			$result = clone $iterationDate;

			if ($this->isLimitDateReached($iterationDate, $limit, $direction)) { break; }

			$iterationDate = $iterationDate->add($interval);
			$iterationDate = $this->limitDate($iterationDate, $limit, $direction);

			++$repeatIndex;
		}

		return $result;
	}

	protected function limitDate(Main\Type\Date $date, Main\Type\Date $limit = null, $direction = false)
	{
		if ($limit === null)
		{
			$result = $date;
		}
		else
		{
			$isDateMoreLimit = $date->getTimestamp() > $limit->getTimestamp();

			if ($isDateMoreLimit === $direction)
			{
				$result = clone $limit;
			}
			else
			{
				$result = $date;
			}
		}

		return $result;
	}

	protected function isLimitDateReached(Main\Type\Date $date, Main\Type\Date $limit = null, $direction = false)
	{
		if ($limit === null)
		{
			$result = false;
		}
		else
		{
			$isDateMoreLimit = $date->getTimestamp() >= $limit->getTimestamp();
			$result = ($isDateMoreLimit === $direction);
		}

		return $result;
	}

	protected function roundDate(Main\Type\Date $date, $direction)
	{
		if ($date instanceof Main\Type\DateTime)
		{
			$result = clone $date;
		}
		else
		{
			$result = Main\Type\DateTime::createFromTimestamp($date->getTimestamp());
		}

		if ($direction)
		{
			$result->setTime(23, 59, 59);
		}
		else
		{
			$result->setTime(0, 0, 0);
		}

		return $result;
	}
}