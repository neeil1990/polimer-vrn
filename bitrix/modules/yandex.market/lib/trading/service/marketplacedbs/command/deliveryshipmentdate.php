<?php

namespace Yandex\Market\Trading\Service\MarketplaceDbs\Command;

use Bitrix\Main;
use Yandex\Market;
use Yandex\Market\Trading\Service as TradingService;
use Yandex\Market\Reference\Assert;

class DeliveryShipmentDate
{
	protected $shipmentSchedule;
	protected $deliveryDate;
	protected $shipmentTimetable;
	protected $deliveryOption;
	protected $deliveryTimetable;
	protected $now;
	protected $calculateDirection;
	protected $calculateOffset;

	public function __construct(
		TradingService\MarketplaceDbs\Options\ShipmentSchedule $shipmentSchedule,
		TradingService\MarketplaceDbs\Options\DeliveryOption $deliveryOption = null,
		Main\Type\Date $deliveryDate = null,
		Main\Type\Date $now = null
	)
	{
		$this->shipmentSchedule = $shipmentSchedule;
		$this->deliveryOption = $deliveryOption !== null ? $deliveryOption : $shipmentSchedule->makeCommonDeliveryOption();
		$this->deliveryDate = $deliveryDate;
		$this->now = $now !== null ? $now : new Main\Type\DateTime();

		$this->shipmentTimetable = new TradingService\MarketplaceDbs\Options\Timetable(
			$this->shipmentSchedule->getSchedule(),
			$this->shipmentSchedule->getHoliday()
		);
		$this->deliveryTimetable = new TradingService\MarketplaceDbs\Options\Timetable(
			$this->deliveryOption->getSchedule(),
			$this->deliveryOption->getHoliday()
		);
	}

	public function setCalculateDirection($direction)
	{
		$this->calculateDirection = (bool)$direction;
	}

	public function getCalculateDirection()
	{
		return $this->calculateDirection !== null
			? $this->calculateDirection
			: $this->deliveryOption->getShipmentDateDirection();
	}

	public function setCalculateOffset($offset)
	{
		$this->calculateOffset = (int)$offset;
	}

	public function getCalculateOffset()
	{
		$result = $this->calculateOffset !== null
			? $this->calculateOffset
			: $this->deliveryOption->getShipmentDateOffset();

		Assert::notNull($result, 'shipmentDateOffset');

		return $result;
	}

	/** @return Main\Type\Date */
	public function execute()
	{
		$direction = $this->getCalculateDirection();
		$offset = $this->getCalculateOffset();
		/** @var Main\Type\Date $startDate */
		$startDate = $this->getStartDate($direction);
		$hasIntersection = $this->shipmentTimetable->hasIntersection($this->deliveryTimetable);
		$result = $startDate;
		$iterationLimit = 100;
		$iterationCount = 0;

		do
		{
			if (++$iterationCount > $iterationLimit)
			{
				throw new Main\SystemException('infinite loop on search intersection of shipment schedule and delivery option');
			}

			$shipmentDate = $this->getShipmentReadyDate($result, $offset, $direction);
			$deliveryDate = $this->getDeliveryWorkingDate($shipmentDate, $hasIntersection ? $direction : true);
			$offset = 0;

			$result = $deliveryDate;

			if (!$hasIntersection) { break; }
		}
		while (Market\Data\Date::compare($shipmentDate, $deliveryDate) !== 0);

		$result = $this->applyLimit($result, $direction);

		return $result;
	}

	protected function getShipmentReadyDate(Main\Type\Date $from, $offset, $direction)
	{
		$result = clone $from;
		$matchRule = $direction
			? TradingService\MarketplaceDbs\Options\ScheduleOption::MATCH_UNTIL_END
			: TradingService\MarketplaceDbs\Options\ScheduleOption::MATCH_AFTER_START;

		if (!$this->shipmentTimetable->isMatch($result, $matchRule))
		{
			++$offset;
		}

		while ($offset > 0)
		{
			$result = $this->shipmentTimetable->getNextWorkingDay($result, !$direction);
			--$offset;
		}

		return $result;
	}

	protected function getDeliveryWorkingDate(Main\Type\Date $from, $direction)
	{
		$matchRule = $direction
			? TradingService\MarketplaceDbs\Options\ScheduleOption::MATCH_UNTIL_END
			: TradingService\MarketplaceDbs\Options\ScheduleOption::MATCH_AFTER_START;

		return $this->deliveryTimetable->isMatch($from, $matchRule)
			? clone $from
			: $this->deliveryTimetable->getNextWorkingDay($from, !$direction);
	}

	protected function getStartDate($direction)
	{
		if ($direction)
		{
			$result = clone $this->now;
			$result = $this->applyDelay($result, true);
		}
		else
		{
			Assert::notNull($this->deliveryDate, 'deliveryDate');
			$result = clone $this->deliveryDate;
		}

		return $result;
	}

	protected function applyDelay(Main\Type\Date $date, $direction)
	{
		if (!$direction) { return $date; }

		$schedule = $this->deliveryOption->getSchedule();
		$assemblyDelay = $this->deliveryOption->getAssemblyDelay();

		if ($assemblyDelay->isEmpty())
		{
			$schedule = $this->shipmentSchedule->getSchedule();
			$assemblyDelay = $this->shipmentSchedule->getAssemblyDelay();

			if ($assemblyDelay->isEmpty()) { return $date; }
		}

		$delayDays = $assemblyDelay->getDays();

		if ($this->isAfterOrderBefore($date, $assemblyDelay))
		{
			++$delayDays;
		}
		else
		{
			$timeDelay = $this->orderBeforeTimeInterval($schedule, $assemblyDelay);

			if ($timeDelay !== null)
			{
				$date->add($timeDelay);
			}
		}

		if ($delayDays > 0)
		{
			$date = $this->getShipmentReadyDate($date, $delayDays, $direction);
		}

		return $date;
	}

	protected function isAfterOrderBefore(
		Main\Type\Date $date,
		TradingService\MarketplaceDbs\Options\AssemblyDelayOption $assemblyDelay
	)
	{
		return Market\Data\Time::compare($assemblyDelay->getTimeBefore(), $date->format('H:i')) === -1;
	}

	protected function orderBeforeTimeInterval(
		TradingService\MarketplaceDbs\Options\ScheduleOptions $schedule,
		TradingService\MarketplaceDbs\Options\AssemblyDelayOption $assemblyDelay
	)
	{
		$untilTime = Market\Data\Time::min(
			$schedule->firstUntilTime(),
			$this->shipmentSchedule->getSchedule()->firstUntilTime()
		);
		$beforeTime = $assemblyDelay->getTimeBefore();
		list($diffSign, $diffTime) = Market\Data\Time::diff($untilTime, $beforeTime);

		if ($diffTime === null || $diffTime === '00:00' || $diffSign === -1) { return null; }

		$diffTime = Market\Data\Time::parse($diffTime);

		return sprintf('PT%sH%sM', (int)$diffTime[0], (int)$diffTime[1]);
	}

	protected function applyLimit(Main\Type\Date $date, $direction)
	{
		if (!$direction)
		{
			$result = Market\Data\Date::max($this->now, $date);
		}
		else if ($this->deliveryDate !== null)
		{
			$result = Market\Data\Date::min($this->deliveryDate, $date);
		}
		else
		{
			$result = $date;
		}

		return $result;
	}
}