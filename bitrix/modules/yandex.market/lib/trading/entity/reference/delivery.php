<?php

namespace Yandex\Market\Trading\Entity\Reference;

use Yandex\Market;
use Bitrix\Main;

class Delivery
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
	 * @return array{ID: string, VALUE: string, TYPE: string|null}[]
	 */
	public function getEnum($siteId = null)
	{
		throw new Market\Exceptions\NotImplementedMethod(static::class, 'getEnum');
	}

	/**
	 * @param int[] $deliveryIds
	 *
	 * @return int[]
	 */
	public function filterServicesWithoutPeriod(array $deliveryIds)
	{
		return $deliveryIds;
	}

	/**
	 * @return int|null
	 */
	public function getEmptyDeliveryId()
	{
		throw new Market\Exceptions\NotImplementedMethod(static::class, 'getEmptyDeliveryId');
	}

	/**
	 * @param Order $order
	 *
	 * @return int[]
	 */
	public function getRestricted(Order $order)
	{
		throw new Market\Exceptions\NotImplementedMethod(static::class, 'getRestricted');
	}

	/**
	 * @param int $deliveryId
	 * @param Order $order
	 *
	 * @return bool
	 */
	public function isCompatible($deliveryId, Order $order)
	{
		throw new Market\Exceptions\NotImplementedMethod(static::class, 'isCompatible');
	}

	/**
	 * @param int $deliveryId
	 * @param Order $order
	 *
	 * @return Delivery\CalculationResult
	 */
	public function calculate($deliveryId, Order $order)
	{
		throw new Market\Exceptions\NotImplementedMethod(static::class, 'calculate');
	}

	/**
	 * @param int $deliveryId
	 * @param string[]|null $supportedTypes
	 *
	 * @return string|null
	 */
	public function suggestDeliveryType($deliveryId, array $supportedTypes = null)
	{
		return null;
	}

	/**
	 * @param int $deliveryId
	 *
	 * @return array
	 */
	public function debugData($deliveryId)
	{
		return [];
	}
}