<?php

namespace Yandex\Market\Trading\Entity\Reference;

use Yandex\Market;

abstract class Pack
{
	protected $environment;

	public function __construct(Environment $environment)
	{
		$this->environment = $environment;
	}

	/**
	 * @param int[] $productIds
	 * @param array $context
	 *
	 * @return array<int, float> $productId => $ratio
	 */
	public function getRatio($productIds, array $context = [])
	{
		throw new Market\Exceptions\NotImplementedMethod(static::class, 'getRatio');
	}
}