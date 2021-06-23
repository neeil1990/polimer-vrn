<?php

namespace Yandex\Market\Trading\Service\Reference;

use Yandex\Market;

class ModelFactory
{
	protected $provider;

	public function __construct(Provider $provider)
	{
		$this->provider = $provider;
	}

	/**
	 * @return Market\Api\Model\Cart
	 */
	public function getCartClassName()
	{
		return Market\Api\Model\Cart::class;
	}

	/**
	 * @return Market\Api\Model\Order
	 */
	public function getOrderClassName()
	{
		return Market\Api\Model\Order::class;
	}
}