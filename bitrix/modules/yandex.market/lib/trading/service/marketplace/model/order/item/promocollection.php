<?php

namespace Yandex\Market\Trading\Service\Marketplace\Model\Order\Item;

use Yandex\Market;
use Bitrix\Main;

class PromoCollection extends Market\Api\Reference\Collection
{
	/** @var Promo[] */
	protected $collection = [];

	public static function getItemReference()
	{
		return Promo::class;
	}
}