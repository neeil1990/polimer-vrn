<?php

namespace Yandex\Market\Trading\Service\MarketplaceDbs\Options;

use Yandex\Market\Trading\Service as TradingService;

/** @method PaySystemOption current() */
class PaySystemOptions extends TradingService\Reference\Options\FieldsetCollection
{
	public function getItemReference()
	{
		return PaySystemOption::class;
	}

	public function getPaySystemIds()
	{
		$result = [];

		/** @var PaySystemOption $option */
		foreach ($this->collection as $option)
		{
			$result[] = $option->getPaySystemId();
		}

		return $result;
	}

	public function getItemsByPaySystemId($paySystemId)
	{
		$paySystemId = (int)$paySystemId;
		$result = [];

		/** @var PaySystemOption $option */
		foreach ($this->collection as $option)
		{
			if ($option->getPaySystemId() === $paySystemId)
			{
				$result[] = $option;
			}
		}

		return $result;
	}
}
