<?php

namespace Yandex\Market\Trading\Entity\Reference;

abstract class CourierRegistry
{
	protected $environment;

	public function __construct(Environment $environment)
	{
		$this->environment = $environment;
	}

	public function getTypes()
	{
		return [];
	}

	/**
	 * @param int $deliveryId
	 *
	 * @return Courier|null
	 */
	public function resolveCourier($deliveryId)
	{
		$result = null;

		foreach ($this->getTypes() as $type)
		{
			$outlet = $this->makeCourier($type);

			if (!$outlet->canLoad() || !$outlet->isMatch($deliveryId)) { continue; }

			$outlet->load();

			$result = $outlet;
			break;
		}

		return $result;
	}

	/**
	 * @param string $type
	 *
	 * @return Courier
	 */
	abstract protected function makeCourier($type);
}