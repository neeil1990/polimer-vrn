<?php

namespace Yandex\Market\Trading\Entity\Reference;

use Yandex\Market;

abstract class Outlet
{
	use Concerns\HasModuleDependency;

	protected $environment;

	public function __construct(Environment $environment)
	{
		$this->environment = $environment;
	}

	public function getTitle()
	{
		throw new Market\Exceptions\NotImplementedMethod(static::class, 'getTitle');
	}

	public function isMatch($deliveryId)
	{
		return false;
	}

	/**
	 * @noinspection PhpUnusedParameterInspection
	 *
	 * @param Order $order
	 * @param int $deliveryId
	 * @param Market\Api\Model\Region $region
	 *
	 * @return string[]
	 */
	public function getOutlets(Order $order, $deliveryId, Market\Api\Model\Region $region)
	{
		return [];
	}

	/**
	 * @noinspection PhpUnusedParameterInspection
	 *
	 * @param int $deliveryId
	 * @param string $code
	 *
	 * @return Market\Api\Model\Outlet
	 */
	public function outletDetails($deliveryId, $code)
	{
		throw new Market\Exceptions\NotImplementedMethod(static::class, 'outletDetails');
	}
}