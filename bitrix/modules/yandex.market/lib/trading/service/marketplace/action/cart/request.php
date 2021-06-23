<?php

namespace Yandex\Market\Trading\Service\Marketplace\Action\Cart;

use Yandex\Market;
use Bitrix\Main;
use Yandex\Market\Trading\Entity as TradingEntity;
use Yandex\Market\Trading\Service as TradingService;

class Request extends TradingService\Common\Action\Cart\Request
{
	/**
	 * @return TradingService\Marketplace\Model\Cart
	 * @throws Market\Exceptions\Api\InvalidOperation
	 */
	public function getCart()
	{
		return $this->getRequiredModel('cart');
	}

	protected function getChildModelReference()
	{
		return [
			'cart' => TradingService\Marketplace\Model\Cart::class
		];
	}
}