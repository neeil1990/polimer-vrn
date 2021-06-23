<?php

namespace Yandex\Market\Trading\Service\MarketplaceDbs\Options;

use Yandex\Market\Trading\Service as TradingService;

/** @method DeliveryOption current() */
class DeliveryOptions extends TradingService\Reference\Options\FieldsetCollection
{
	public function getItemReference()
	{
		return DeliveryOption::class;
	}

	public function getServiceIds()
	{
		$result = [];

		/** @var DeliveryOption $option */
		foreach ($this->collection as $option)
		{
			$result[] = $option->getServiceId();
		}

		return $result;
	}

	public function getItemByServiceId($serviceId)
	{
		$serviceId = (int)$serviceId;
		$result = null;

		/** @var DeliveryOption $option */
		foreach ($this->collection as $option)
		{
			if ($option->getServiceId() === $serviceId)
			{
				$result = $option;
				break;
			}
		}

		return $result;
	}
}