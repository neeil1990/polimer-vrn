<?php

namespace Yandex\Market\Trading\Service\Marketplace\Model;

use Yandex\Market;
use Bitrix\Main;
use Yandex\Market\Trading\Service as TradingService;

/** @method Cart\ItemCollection getItems() */
class Cart extends Market\Api\Model\Cart
{
	protected function getChildCollectionReference()
	{
		return [
			'items' => Cart\ItemCollection::class,
		];
	}
}