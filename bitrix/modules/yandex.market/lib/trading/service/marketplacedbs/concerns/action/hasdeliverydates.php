<?php

namespace Yandex\Market\Trading\Service\MarketplaceDbs\Concerns\Action;

use Yandex\Market;
use Yandex\Market\Trading\Entity as TradingEntity;
use Yandex\Market\Trading\Service as TradingService;

/**
 * trait HasMeaningfulProperties
 * @property TradingService\Common\Provider $provider
 * @property TradingEntity\Reference\Environment $environment
 * @property TradingService\Turbo\Action\OrderAccept\Request|TradingService\Turbo\Action\OrderStatus\Request $request
 * @property TradingEntity\Reference\Order $order
 * @method setMeaningfulPropertyValues($propertyValues)
 */
trait HasDeliveryDates
{
	protected function fillDeliveryDatesProperties()
	{
		$dates = $this->request->getOrder()->getDelivery()->getDates();

		if ($dates !== null)
		{
			$propertyValues = $this->getDeliveryDatesProperties($dates);

			$this->setMeaningfulPropertyValues($propertyValues);
		}
	}

	protected function getDeliveryDatesProperties(TradingService\MarketplaceDbs\Model\Order\Delivery\Dates $dates)
	{
		return [
			'DELIVERY_DATE_FROM' => $dates->getFromDate(),
			'DELIVERY_DATE_TO' => $dates->getToDate(),
			'DELIVERY_INTERVAL_FROM' => $dates->getFromTime(),
			'DELIVERY_INTERVAL_TO' => $dates->getToTime(),
		];
	}
}