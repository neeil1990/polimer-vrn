<?php

namespace Yandex\Market\Trading\Service\Marketplace\Model\Order\Item;

use Yandex\Market;
use Bitrix\Main;

/** @property Instance[] $collection */
class InstanceCollection extends Market\Api\Reference\Collection
{
	public static function getItemReference()
	{
		return Instance::class;
	}
}