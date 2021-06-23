<?php

namespace Yandex\Market\Trading\Service\Common\Action\Cart;

use Yandex\Market;
use Bitrix\Main;
use Yandex\Market\Trading\Service as TradingService;

class Request extends TradingService\Common\Action\HttpRequest
{
	/**
	 * @return Market\Api\Model\Cart
	 * @throws Market\Exceptions\Api\InvalidOperation
	 */
	public function getCart()
	{
		return $this->getRequiredModel('cart');
	}

	protected function getChildModelReference()
	{
		return [
			'cart' => Market\Api\Model\Cart::class
		];
	}
}