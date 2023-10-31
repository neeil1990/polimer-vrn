<?php

namespace Yandex\Market\Trading\Service\MarketplaceDbs\Command;

use Yandex\Market;
use Yandex\Market\Trading\Service as TradingService;
use Bitrix\Main;

class DeliveryIntervalsMake
{
	/** @var TradingService\MarketplaceDbs\Options\DeliveryOption */
	protected $deliveryOption;
	/** @var Main\Type\Date */
	protected $dateFrom;
	/** @var Main\Type\Date */
	protected $dateTo;
	/** @var Main\Type\Date|null */
	protected $minDate;
	/** @var int */
	protected $maxDaysCount = 1;
	/** @var TradingService\MarketplaceDbs\Options\Timetable */
	protected $timetable;
	/** @var Main\Type\DateTime */
	protected $now;

	public function __construct(
		TradingService\MarketplaceDbs\Options\DeliveryOption $deliveryOption,
		Main\Type\Date $dateFrom,
		Main\Type\Date $dateTo = null,
		Main\Type\Date $now = null
	)
	{
		$this->deliveryOption = $deliveryOption;
		$this->dateFrom = $dateFrom;
		$this->dateTo = $dateTo !== null ? $dateTo : clone $dateFrom;
		$this->timetable = new TradingService\MarketplaceDbs\Options\Timetable(
			$this->deliveryOption->getSchedule(),
			$this->deliveryOption->getHoliday()
		);
		$this->now = $now === null ? new Main\Type\Date() : $now;
	}

	public function setMinDate(Main\Type\Date $dateTime)
	{
		$this->minDate = $dateTime;
	}

	public function setMaxDaysCount($count)
	{
		$this->maxDaysCount = (int)$count;
	}

	public function canExecute()
	{
		return (
			$this->deliveryOption->getSchedule()->hasValid()
			|| !$this->deliveryOption->getHoliday()->isEmpty()
		);
	}

	public function execute()
	{
		list($startDate, $minDate, $moveFrom, $moveTo) = $this->starter();
		$daysCount = 0;
		$intervals = [];
		$iterator = clone $startDate;
		$dateTo = $moveTo
			? clone Market\Data\Date::max($this->dateTo, $minDate, $startDate)
			: clone $this->dateTo;

		do
		{
			$minDateCompare = $minDate !== null ? Market\Data\Date::compare($iterator, $minDate) : 1;
			$dayIntervals = [];

			if ($minDateCompare >= 0 || $moveFrom || $moveTo)
			{
				$dayIntervals = $this->timetable->getIntervals($iterator);
			}

			if (empty($dayIntervals))
			{
				if ($moveFrom && $minDateCompare === -1) { $minDate->add('P1D'); }
				if ($moveTo) { $dateTo->add('P1D'); }
			}
			else if ($minDateCompare >= 0)
			{
				foreach ($dayIntervals as $interval)
				{
					$intervals[] = [
						'date' => clone $iterator,
						'fromTime' => $interval->getFromTime(),
						'toTime' => $interval->getToTime(),
					];
				}

				++$daysCount;
			}

			$iterator->add('P1D');
		}
		while (
			$daysCount < $this->maxDaysCount
			&& (
				($minDateCompare !== 1 && $daysCount === 0)
				|| Market\Data\Date::compare($iterator, $dateTo) !== 1
			)
		);

		return $intervals;
	}

	protected function starter()
	{
		$rule = $this->deliveryOption->getPeriodWeekendRule();

		if ($rule === TradingService\MarketplaceDbs\Options\DeliveryOption::PERIOD_WEEKEND_RULE_FULL)
		{
			$startDate = $this->minDate !== null ? $this->minDate : $this->now;
			$minDate = $this->dateFrom;
			$moveFrom = true;
			$moveTo = true;
		}
		else if ($rule === TradingService\MarketplaceDbs\Options\DeliveryOption::PERIOD_WEEKEND_RULE_EDGE)
		{
			$startDate =  $this->dateFrom;
			$minDate = $this->minDate;
			$moveFrom = false;
			$moveTo = true;
		}
		else
		{
			$startDate =  $this->dateFrom;
			$minDate = $this->minDate;
			$moveFrom = false;
			$moveTo = false;
		}

		return [$startDate, $minDate, $moveFrom, $moveTo];
	}

	/** @deprecated */
	protected function getDaysDiff(Main\Type\Date $from, Main\Type\Date $to)
	{
		$fromDate = (new \DateTime())->setTimestamp($from->getTimestamp());
		$toDate = (new \DateTime())->setTimestamp($to->getTimestamp());
		$interval = $toDate->diff($fromDate);

		return (int)$interval->format('%a');
	}
}