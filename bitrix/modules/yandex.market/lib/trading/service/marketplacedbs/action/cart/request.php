<?php

namespace Yandex\Market\Trading\Service\MarketplaceDbs\Action\Cart;

use Yandex\Market;
use Bitrix\Main;
use Yandex\Market\Trading\Entity as TradingEntity;
use Yandex\Market\Trading\Service as TradingService;

class Request extends TradingService\Marketplace\Action\Cart\Request
{
	/**
	 * @return TradingService\MarketplaceDbs\Model\Cart
	 * @throws Market\Exceptions\Api\InvalidOperation
	 */
	public function getCart()
	{
		return $this->getRequiredModel('cart');
	}

	protected function getChildModelReference()
	{
		return [
			'cart' => TradingService\MarketplaceDbs\Model\Cart::class
		];
	}
}