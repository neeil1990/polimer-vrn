<?php

namespace Yandex\Market\Api\Model\Order;

use Bitrix\Main;
use Yandex\Market;

/** @property Item[] $collection */
class ItemCollection extends Market\Api\Model\Cart\ItemCollection
{
	public static function getItemReference()
	{
		return Item::class;
	}

	public function getSum()
	{
		$result = 0;

		foreach ($this->collection as $item)
		{
			$result += $item->getPrice() * $item->getCount();
		}

		return $result;
	}

	public function getSubsidySum()
	{
		$result = 0;

		foreach ($this->collection as $item)
		{
			$result += $item->getSubsidy() * $item->getCount();
		}

		return $result;
	}
}