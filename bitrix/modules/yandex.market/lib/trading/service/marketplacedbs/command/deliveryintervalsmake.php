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
		$daysCount = 0;
		$increasePeriodOnWeekend = $this->deliveryOption->increasePeriodOnWeekend();
		$intervals = [];
		$iterator = clone $this->dateFrom;
		$dateTo = clone $this->dateTo;

		if ($increasePeriodOnWeekend && $this->minDate !== null && Market\Data\Date::compare($this->now, $this->minDate) === -1)
		{
			$shipDelayDays = $this->getDaysDiff($this->now, $this->minDate);
			$shipDelayInterval = sprintf('P%sD', $shipDelayDays);

			$iterator->add($shipDelayInterval);
			$dateTo->add($shipDelayInterval);
		}

		do
		{
			$minDateCompare = $this->minDate !== null ? Market\Data\Date::compare($iterator, $this->minDate) : 1;
			$dayIntervals = ($minDateCompare !== -1) ? $this->timetable->getIntervals($iterator) : [];

			if (!empty($dayIntervals))
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
			else if ($increasePeriodOnWeekend)
			{
				$dateTo->add('P1D');
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

	protected function getDaysDiff(Main\Type\Date $from, Main\Type\Date $to)
	{
		$fromDate = (new \DateTime())->setTimestamp($from->getTimestamp());
		$toDate = (new \DateTime())->setTimestamp($to->getTimestamp());
		$interval = $toDate->diff($fromDate);

		return (int)$interval->format('%a');
	}
}