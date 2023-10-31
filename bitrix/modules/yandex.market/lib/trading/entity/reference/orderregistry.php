<?php

namespace Yandex\Market\Trading\Entity\Reference;

use Yandex\Market;
use Bitrix\Main;

abstract class OrderRegistry
{
	protected $environment;

	public function __construct(Environment $environment)
	{
		$this->environment = $environment;
	}

	/**
	 * @param Platform $platform
	 *
	 * @return string
	 */
	public function getAdminListUrl(Platform $platform)
	{
		throw new Market\Exceptions\NotImplementedMethod(static::class, 'getAdminListUrl');
	}

	/**
	 * @param string $siteId
	 * @param int|null $userId
	 * @param string|null $currency
	 *
	 * @return Order
	 */
	public function createOrder($siteId, $userId, $currency)
	{
		throw new Market\Exceptions\NotImplementedMethod(static::class, 'createOrder');
	}

	/**
	 * @param $orderIds
	 *
	 * @return Order[]
	 */
	public function loadOrderList($orderIds)
	{
		throw new Market\Exceptions\NotImplementedMethod(static::class, 'loadOrderList');
	}

	/**
	 * @param int $orderId
	 *
	 * @return Order
	 * @throws Main\ObjectNotFoundException
	 */
	public function loadOrder($orderId)
	{
		throw new Market\Exceptions\NotImplementedMethod(static::class, 'loadOrder');
	}

	/**
	 * @param int $orderId
	 * @param string $code
	 * @param string|null $condition
	 *
	 * @return bool
	 */
	public function isExistMarker($orderId, $code, $condition = null)
	{
		throw new Market\Exceptions\NotImplementedMethod(static::class, 'isExistMarker');
	}

	/**
	 * @param string[] $externalIds
	 * @param Platform $platform
	 * @param bool|null $useAccountNumber
	 *
	 * @return array<string, string>
	 */
	public function searchList($externalIds, Platform $platform, $useAccountNumber = null)
	{
		throw new Market\Exceptions\NotImplementedMethod(static::class, 'searchList');
	}

	/**
	 * @param string $externalId
	 * @param Platform $platform
	 * @param bool|null $useAccountNumber
	 *
	 * @return string|null
	 */
	public function search($externalId, Platform $platform, $useAccountNumber = null)
	{
		throw new Market\Exceptions\NotImplementedMethod(static::class, 'search');
	}

	/**
	 * @param string[] $externalIds
	 * @param Platform $platform
	 * @param bool|null $useAccountNumber
	 *
	 * @return string|null
	 */
	public function searchBrokenList($externalIds, Platform $platform, $useAccountNumber = null)
	{
		throw new Market\Exceptions\NotImplementedMethod(static::class, 'searchBrokenList');
	}

	/**
	 * @param string $externalId
	 * @param Platform $platform
	 * @param bool|null $useAccountNumber
	 *
	 * @return string|null
	 */
	public function searchBroken($externalId, Platform $platform, $useAccountNumber = null)
	{
		throw new Market\Exceptions\NotImplementedMethod(static::class, 'searchBroken');
	}

	/**
	 * @param string $value
	 * @param string $field
	 * @param Platform $platform
	 *
	 * @return string[]
	 */
	public function suggestExternalIds($value, $field, Platform $platform)
	{
		throw new Market\Exceptions\NotImplementedMethod(static::class, 'suggestExternalIds');
	}
}