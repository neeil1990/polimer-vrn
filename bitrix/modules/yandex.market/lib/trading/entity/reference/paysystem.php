<?php

namespace Yandex\Market\Trading\Entity\Reference;

use Yandex\Market;
use Bitrix\Main;

class PaySystem
{
	protected $environment;

	public function __construct(Environment $environment)
	{
		$this->environment = $environment;
	}

	/**
	 * @return bool
	 */
	public function isRequired()
	{
		return false;
	}

	/**
	 * @param string|null $siteId
	 *
	 * @return array{ID: string, VALUE: string}[]
	 */
	public function getEnum($siteId = null)
	{
		throw new Market\Exceptions\NotImplementedMethod(static::class, 'getEnum');
	}

	/**
	 * @param Order $order
	 * @param int|null $deliveryId
	 *
	 * @return int[]
	 */
	public function getCompatible(Order $order, $deliveryId = null)
	{
		throw new Market\Exceptions\NotImplementedMethod(static::class, 'getCompatible');
	}

	/**
	 * @param int $paySystemId
	 * @param string[]|null $supportedMethods
	 *
	 * @return string[]|null
	 */
	public function suggestPaymentMethod($paySystemId, array $supportedMethods = null)
	{
		throw new Market\Exceptions\NotImplementedMethod(static::class, 'suggestPaymentMethod');
	}
}