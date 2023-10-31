<?php

namespace Yandex\Market\Api\Model\Cart;

use Yandex\Market;
use Bitrix\Main;

class ItemCollection extends Market\Api\Reference\Collection
{
	/** @var Item[] */
	protected $collection;

	public static function getItemReference()
	{
		return Item::class;
	}

	public function getOfferIds()
	{
		$result = [];

		foreach ($this->collection as $item)
		{
			$result[] = $item->getOfferId();
		}

		return $result;
	}

	public function getQuantities($offerMap = null)
	{
		$result = [];

		foreach ($this->collection as $item)
		{
			$productId = $item->mapProductId($offerMap);

			if ($productId === null) { continue; }

			if (!isset($result[$productId]))
			{
				$result[$productId] = [];
			}

			$result[$productId][] = $item->getCount();
		}

		return $result;
	}
}