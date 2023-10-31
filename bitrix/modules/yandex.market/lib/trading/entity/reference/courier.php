<?php

namespace Yandex\Market\Trading\Entity\Reference;

abstract class Courier
{
	use Concerns\HasModuleDependency;

	protected $environment;

	public function __construct(Environment $environment)
	{
		$this->environment = $environment;
	}

	public function isMatch($deliveryId)
	{
		return false;
	}
}