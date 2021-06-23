<?php

namespace Yandex\Market\Trading\Service\MarketplaceDbs\Options;

use Bitrix\Main;
use Yandex\Market\Trading\Service as TradingService;

/** @method IntervalOption current() */
/** @property IntervalOption[] collection */
class IntervalOptions extends TradingService\Reference\Options\FieldsetCollection
{
	public function getItemReference()
	{
		return IntervalOption::class;
	}

	public function hasValid()
	{
		$result = false;

		foreach ($this->collection as $option)
		{
			if ($option->isValid())
			{
				$result = true;
				break;
			}
		}

		return $result;
	}
}