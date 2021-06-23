<?php

namespace Yandex\Market\Trading\Service\Marketplace\Model\Order\Item;

use Yandex\Market;
use Bitrix\Main;

class Instance extends Market\Api\Reference\Model
{
	/** @return string|null */
	public function getCis()
	{
		return $this->getField('cis');
	}
}