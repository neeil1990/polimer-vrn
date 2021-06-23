<?php

namespace Yandex\Market\Trading\Service\Common\Action\OrderAccept;

use Yandex\Market;
use Bitrix\Main;
use Yandex\Market\Trading\Service as TradingService;

class Request extends TradingService\Common\Action\Cart\Request
{
	/**
	 * @return Market\Api\Model\Order
	 * @throws Market\Exceptions\Api\InvalidOperation
	 */
	public function getOrder()
	{
		return $this->getRequiredModel('order');
	}

	public function getCart()
	{
		return $this->getOrder();
	}

	protected function getChildModelReference()
	{
		return [
			'order' => Market\Api\Model\Order::class,
		];
	}
}