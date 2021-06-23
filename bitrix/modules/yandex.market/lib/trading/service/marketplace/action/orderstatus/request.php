<?php

namespace Yandex\Market\Trading\Service\Marketplace\Action\OrderStatus;

use Yandex\Market;
use Bitrix\Main;
use Yandex\Market\Trading\Service as TradingService;

class Request extends TradingService\Common\Action\OrderStatus\Request
{
	/**
	 * @return TradingService\Marketplace\Model\Order
	 * @throws Market\Exceptions\Api\InvalidOperation
	 */
	public function getOrder()
	{
		return $this->getRequiredModel('order');
	}

	protected function getChildModelReference()
	{
		return [
			'order' => TradingService\Marketplace\Model\Order::class,
		];
	}
}