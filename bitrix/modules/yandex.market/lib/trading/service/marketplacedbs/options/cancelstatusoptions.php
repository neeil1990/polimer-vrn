<?php

namespace Yandex\Market\Trading\Service\MarketplaceDbs\Options;

use Yandex\Market\Trading\Service as TradingService;

/** @method CancelStatusOption current() */
class CancelStatusOptions extends TradingService\Reference\Options\FieldsetCollection
{
	public function getItemReference()
	{
		return CancelStatusOption::class;
	}

	public function hasStatus($bitrixStatus)
	{
		$result = false;

		foreach ($this->collection as $cancelStatusOption)
		{
			if ($cancelStatusOption->getStatus() === $bitrixStatus)
			{
				$result = true;
				break;
			}
		}

		return $result;
	}
}
