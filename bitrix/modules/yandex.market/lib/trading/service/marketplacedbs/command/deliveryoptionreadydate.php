<?php

namespace Yandex\Market\Trading\Service\MarketplaceDbs\Command;

use Yandex\Market;
use Yandex\Market\Trading\Service as TradingService;
use Bitrix\Main;

class DeliveryOptionReadyDate
{
	/** @var TradingService\MarketplaceDbs\Options\DeliveryOption */
	protected $deliveryOption;
	/** @var Main\Type\Date */
	protected $now;
	/** @var Main\Type\Date|null */
	protected $minDate;
	/** @var TradingService\MarketplaceDbs\Options\Timetable */
	protected $timetable;

	public function __construct(
		TradingService\MarketplaceDbs\Options\DeliveryOption $deliveryOption,
		Main\Type\Date $now = null
	)
	{
		$this->deliveryOption = $deliveryOption;
		$this->now = $now === null ? new Main\Type\DateTime() : $now;
		$this->timetable = new TradingService\MarketplaceDbs\Options\Timetable(
			$this->deliveryOption->getSchedule(),
			$this->deliveryOption->getHoliday()
		);
	}

	public function execute()
	{
		$delay = Market\Data\Time::makeIntervalString($this->deliveryOption->getShipmentDelay());
		$result = clone $this->now;

		if ($delay !== null)
		{
			$result->add($delay);
		}

		if (!$this->timetable->isMatch($result))
		{
			$result = $this->getNextWorkingDay($result);
		}

		return $result;
	}

	protected function getNextWorkingDay(Main\Type\Date $date)
	{
		$result = clone $date;

		do
		{
			$result->add('P1D');

			$intervals = $this->timetable->getIntervals($result);
			$isMatch = !empty($intervals);

			if ($isMatch && $result instanceof Main\Type\DateTime)
			{
				$result = $this->timetable->applyIntervals($result, $intervals);
			}
		}
		while (!$isMatch);

		return $result;
	}
}