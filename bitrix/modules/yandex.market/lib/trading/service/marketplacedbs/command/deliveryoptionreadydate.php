<?php

namespace Yandex\Market\Trading\Service\MarketplaceDbs\Command;

use Yandex\Market;
use Yandex\Market\Trading\Service as TradingService;
use Bitrix\Main;

/** @deprecated */
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

		if (!$this->timetable->isMatch($result, TradingService\MarketplaceDbs\Options\ScheduleOption::MATCH_UNTIL_END))
		{
			$result = $this->timetable->getNextWorkingDay($result);
		}

		return $result;
	}

	/**
	 * @deprecated
	 * @param Main\Type\Date $date
	 *
	 * @return Main\Type\Date
	 */
	protected function getNextWorkingDay(Main\Type\Date $date)
	{
		return $this->timetable->getNextWorkingDay($date);
	}
}