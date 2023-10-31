<?php

namespace Yandex\Market\Trading\Entity\Reference;

abstract class DigitalRegistry
{
	protected $environment;

	public function __construct(Environment $environment)
	{
		$this->environment = $environment;
	}

	public function getAdapters()
	{
		$result = [];

		foreach ($this->getTypes() as $type)
		{
			$digital = $this->makeDigital($type);

			if (!$digital->canLoad()) { continue; }

			$result[$type] = $digital;
		}

		return $result;
	}

	public function getTypes()
	{
		return [];
	}

	/** @return Digital */
	abstract public function makeDigital($type, array $settings = []);
}