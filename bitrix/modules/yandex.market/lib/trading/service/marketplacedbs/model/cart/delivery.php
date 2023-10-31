<?php

namespace Yandex\Market\Trading\Service\MarketplaceDbs\Model\Cart;

use Yandex\Market;
use Bitrix\Main;
use Yandex\Market\Trading\Service as TradingService;

class Delivery extends Market\Api\Model\Cart\Delivery
{
	/**
	 * @return Delivery\Address|null
	 */
	public function getAddress()
	{
		return $this->getChildModel('address');
	}

	protected function getChildModelReference()
	{
		$result = [
			'address' => Delivery\Address::class,
		];

		return $result + parent::getChildModelReference();
	}
}