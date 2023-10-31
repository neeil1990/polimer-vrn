<?php

namespace Yandex\Market\Trading\Entity\Reference;

abstract class OutletRegistry
{
	protected $environment;

	public function __construct(Environment $environment)
	{
		$this->environment = $environment;
	}

	public function getEnum()
	{
		$result = [];

		foreach ($this->getTypes() as $type)
		{
			$outlet = $this->makeOutlet($type);

			if (!$outlet->canLoad()) { continue; }

			$result[] = [
				'ID' => $type,
				'VALUE' => $outlet->getTitle(),
				'INVERTIBLE' => $outlet instanceof OutletInvertible,
				'SELECTABLE' => $outlet instanceof OutletSelectable,
			];
		}

		return $result;
	}

	public function getTypes()
	{
		return [];
	}

	/**
	 * @param int $deliveryId
	 *
	 * @return Outlet|null
	 */
	public function resolveOutlet($deliveryId)
	{
		$result = null;

		foreach ($this->getTypes() as $type)
		{
			$outlet = $this->makeOutlet($type);

			if (!$outlet->canLoad() || !$outlet->isMatch($deliveryId)) { continue; }

			$outlet->load();

			$result = $outlet;
			break;
		}

		return $result;
	}

	public function getOutlet($type)
	{
		$outlet = $this->makeOutlet($type);
		$outlet->load();

		return $outlet;
	}

	/**
	 * @param string $type
	 *
	 * @return Outlet
	 */
	abstract protected function makeOutlet($type);
}