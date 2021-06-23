<?php

namespace Yandex\Market\Trading\Service\Turbo\Model;

use Yandex\Market;
use Bitrix\Main;
use Yandex\Market\Trading\Service as TradingService;

class Order extends Market\Api\Model\Order
{
	/**
	 * @return Order\User
	 * @throws Main\ObjectPropertyException
	 */
	public function getUser()
	{
		return $this->getRequiredModel('user');
	}

	protected function getChildModelReference()
	{
		$parentResult = parent::getChildModelReference();
		$overrides = [
			'user' => Order\User::class,
		];

		return $overrides + $parentResult;
	}
}