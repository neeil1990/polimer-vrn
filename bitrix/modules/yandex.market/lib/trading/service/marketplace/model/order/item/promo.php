<?php

namespace Yandex\Market\Trading\Service\Marketplace\Model\Order\Item;

use Yandex\Market;
use Bitrix\Main;

class Promo extends Market\Api\Reference\Model
{
	public function getMarketPromoId()
	{
		$result = $this->getField('marketPromoId');

		return $result !== null ? (string)$result : null;
	}

	public function getShopPromoId()
	{
		$result = $this->getField('shopPromoId');

		return $result !== null ? (string)$result : null;
	}

	public function getSubsidy()
	{
		return Market\Data\Number::normalize($this->getField('subsidy'));
	}

	public function getType()
	{
		return (string)$this->getRequiredField('type');
	}
}