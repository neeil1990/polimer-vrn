<?php

namespace Yandex\Market\Trading\Service\MarketplaceDbs\Action\OrderStatus;

use Yandex\Market;
use Bitrix\Main;
use Yandex\Market\Trading\Service as TradingService;

class Request extends TradingService\Marketplace\Action\OrderStatus\Request
{
	/**
	 * @return TradingService\MarketplaceDbs\Model\Order
	 * @throws Market\Exceptions\Api\InvalidOperation
	 */
	public function getOrder()
	{
		return $this->getRequiredModel('order');
	}

	protected function getChildModelReference()
	{
		return [
			'order' => TradingService\MarketplaceDbs\Model\Order::class,
		];
	}
}