<?php

namespace Yandex\Market\Trading\Service\Marketplace\Model\Order;

use Yandex\Market;
use Bitrix\Main;
use Yandex\Market\Trading\Service as TradingService;

/** @method Item current() */
class ItemCollection extends Market\Api\Model\Order\ItemCollection
{
	/** @var Item[] */
	protected $collection = [];

	public static function getItemReference()
	{
		return Item::class;
	}

	public function positiveCount()
	{
		$result = 0;

		foreach ($this->collection as $item)
		{
			if ($item->getCount() > 0)
			{
				++$result;
			}
		}

		return $result;
	}
}