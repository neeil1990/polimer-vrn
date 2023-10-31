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
	protected function makeDeliveryDatesData(Market\Api\Model\Order $order = null)
	{
		if ($order === null) { $order = $this->request->getOrder(); }
		if (!$order->hasDelivery()) { return; }

		$delivery = $order->getDelivery();
		$dates = $delivery->getDates();
		$deliveryDate = $dates !== null ? $dates->getFrom() : null;
		$realDeliveryDate = $dates !== null ? $dates->getRealDeliveryDate() : null;
		$deliveryStorageLimit = $delivery->getOutletStorageLimitDate();

		$result = [
			'DELIVERY_DATE' => $deliveryDate !== null
				? $deliveryDate->format(Market\Data\Date::FORMAT_DEFAULT_SHORT)
				: null,
			'DELIVERY_STORAGE_LIMIT' => $deliveryStorageLimit !== null
				? $deliveryStorageLimit->format(Market\Data\Date::FORMAT_DEFAULT_SHORT)
				: null,
		];

		if ($realDeliveryDate !== null)
		{
			$result['REAL_DELIVERY_DATE'] = $realDeliveryDate->format(Market\Data\Date::FORMAT_DEFAULT_SHORT);
		}

		return $result;
	}

	protected function fillDeliveryDatesProperties(Market\Api\Model\Order $order = null)
	{
		if ($order === null) { $order = $this->request->getOrder(); }
		if (!$order->hasDelivery()) { return; }

		$delivery = $order->getDelivery();
		$dates = $delivery->getDates();

		$propertyValues = $this->getDeliverySelfDatesProperties($delivery);
		$propertyValues += $dates !== null ? $this->getDeliveryDatesProperties($dates) : [];

		$this->setMeaningfulPropertyValues($propertyValues);
	}

	protected function getDeliverySelfDatesProperties(Market\Api\Model\Order\Delivery $delivery)
	{
		$result = [];

		if ($delivery instanceof TradingService\MarketplaceDbs\Model\Order\Delivery)
		{
			$result['OUTLET_STORAGE_LIMIT_DATE'] = $delivery->getOutletStorageLimitDate();
		}

		return $result;
	}

	protected function getDeliveryDatesProperties(Market\Api\Model\Order\Dates $dates)
	{
		$realDeliveryDate = $dates->getRealDeliveryDate();
		$result = [
			'DELIVERY_DATE_FROM' => $dates->getFromDate(),
			'DELIVERY_DATE_TO' => $dates->getToDate(),
			'DELIVERY_INTERVAL_FROM' => $dates->getFromTime(),
			'DELIVERY_INTERVAL_TO' => $dates->getToTime(),
		];

		if ($realDeliveryDate !== null)
		{
			$result['DELIVERY_REAL_DATE'] = $dates->getRealDeliveryDate();
		}

		return $result;
	}
}