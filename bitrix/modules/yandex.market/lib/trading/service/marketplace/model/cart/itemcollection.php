<?php

namespace Yandex\Market\Trading\Service\Marketplace\Model\Cart;

use Yandex\Market;
use Bitrix\Main;
use Yandex\Market\Trading\Service as TradingService;

class ItemCollection extends Market\Api\Model\Cart\ItemCollection
{
	/** @var Item[] */
	protected $collection = [];

	public static function getItemReference()
	{
		return Item::class;
	}
}